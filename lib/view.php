<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class View extends Object
   {
      static public $handlers = array(
         'thtml'   => 'php',
         'textile' => 'textilize',
      );

      # Find a template for the given path
      static function find_template($template) {
         static $_paths;
         static $_extensions;

         # Look in /app/views first, then in /lib/views
         if (!$_paths) {
            $_paths = '{'.VIEWS.','.LIB.'views/}';
         }

         # Look for all supported template extensions
         if (!$_extensions) {
            $_extensions = '{'.implode(',', array_keys(self::$handlers)).'}';
         }

         # Look for templates with a language suffix first (e.g. index.en.thtml)
         if ($lang = config('language')) {
            $lang = '{.'.config('language').',}';
         }

         # Return the first match
         return array_shift(glob("$_paths$template$lang.$_extensions", GLOB_BRACE));
      }

      protected $_template;
      protected $_layout;
      protected $_data;

      protected $_partial;
      protected $_locals;

      protected $_cache_key;
      protected $_cache_full;

      function __construct($template=null, $layout=null) {
         $this->_template = $template;
         $this->_layout = $layout;

         # Provide a reference to the HTML builder
         $this->set('html', HtmlBuilder::instance());
      }

      function __toString() {
         return parent::__toString($this->_data);
      }

      function get_template_name() {
         return substr(basename($this->_template), 0, -6);
      }

      function get_template() {
         return $this->_template;
      }

      function set_template($template) {
         return $this->_template = $template;
      }

      function get_layout() {
         return $this->_layout;
      }

      function set_layout($layout) {
         return $this->_layout = $layout;
      }

      function get_data() {
         return (array) $this->_data;
      }

      function set_data($data) {
         $this->_data = (array) $data;
         return $this;
      }

      # Get and set template values
      function get($key) {
         return $this->_data[$key];
      }

      function set($key, $value) {
         $this->_data[$key] = &$value;
         return $this;
      }

      function set_default($key, $value) {
         if (!array_key_exists($key, $this->_data)) {
            $this->set($key, &$value);
         }

         return $this;
      }

      # Render a template
      function render($template=null, $layout=null) {
         if (!$this->_template and !$this->_template = $template) {
            throw new ApplicationError("No template set");
         } elseif (!is_file($this->_template)) {
            if (is_file($file = View::find_template($this->_template))) {
               $this->_template = $file;
            } else {
               throw new MissingTemplate("Template '{$this->_template}.thtml' not found");
            }
         }

         if ($this->_cache_key === true) {
            $this->_cache_key = "view_".urlencode($this->_template);
         }

         # Render the template
         if ($this->_cache_key and $output = cache($this->_cache_key)) {
            if ($this->_cache_full) {
               # Return the full cached page
               log_info("Using cached page '{$this->_cache_key}'");
               return $output;
            } else {
               # Use the cache as content for the layout
               log_info("Using cached content '{$this->_cache_key}'");
            }
         }

         Dispatcher::$render_time -= microtime(true);

         if (!$output) {
            # Render the template
            log_info(sprintf('Rendering '.str_replace(VIEWS, '', $this->_template)));
            $output = $this->compile($this->_template, $this->_data);

            if ($this->_cache_key and !$this->_cache_full) {
               log_info("Caching content as '{$this->_cache_key}'");
               cache_set($this->_cache_key, $output);
            }
         }

         $this->set('content_for_layout', $output);

         if (!is_null($layout)) {
            $this->_layout = $layout;
         }

         if ($this->_layout and !is_file($this->_layout)) {
            if (is_file($_file = View::find_template("layouts/{$this->_layout}"))) {
               $this->_layout = $_file;
            } else {
               throw new MissingTemplate("Layout '{$this->_layout}' not found");
            }
         }

         if (is_file($this->_layout)) {
            # Render the layout
            log_info('Rendering template within '.str_replace(VIEWS, '', $this->_layout));
            $output = $this->compile($this->_layout, $this->_data);
         }

         if ($this->_cache_key and $this->_cache_full) {
            log_info("Caching page as '{$this->_cache_key}'");
            cache_set($this->_cache_key, $output);
         }

         Dispatcher::$render_time += microtime(true);

         return $output;
      }

      # Render a partial template
      function render_partial($partial, array $locals=null) {
         if (strstr($partial, '/') !== false) {
            $partial = dirname($partial).'/_'.basename($partial);
         } else {
            $partial = substr(dirname(any($this->_template, $this->_partial)), strlen(VIEWS)).'/_'.$partial;
         }

         if (!$this->_partial = View::find_template($partial) and
             !$this->_partial = View::find_template(VIEWS.basename($partial))) {
            throw new ApplicationError("Partial '$partial' not found");
         }

         log_info("Rendering partial {$this->_partial}");
         $locals = array_merge((array) $this->_data, (array) $locals);
         return $this->compile($this->_partial, $locals);
      }

      # Render a collection of model instances using partials
      # based on their class name
      function render_collection($objects) {
         $output = '';
         foreach ($objects as $object) {
            if (is_object($object)) {
               $model = underscore(get_class($object));
               $output .= $this->render_partial(
                  $model, array($model => $object)
               );
            } else {
               throw new TypeError($object);
            }
         }

         return $output;
      }

      function cache($key=true, $full=false) {
         $this->_cache_key = $key;
         $this->_cache_full = $full;
      }

      # Compile a template file
      protected function compile($_template, $_locals=null) {
         # Extract assigned values as local variables
         if (extract((array) $_locals, EXTR_SKIP) != count($_locals)) {
            #throw new ApplicationError("Couldn't extract all template variables");
         }

         ob_start();
         require $_template;
         $output = ob_get_clean();

         $ext = substr($_template, strrpos($template, '.') + 1);
         if ($handler = self::$handlers[$ext] and $handler != 'php') {
            if (function_exists($handler)) {
               log_info("Applying template handler '$handler'");
               return $handler($output);
            } elseif (class_exists($handler)) {
               log_info("Applying template handler '$handler'");
               $handler = new $handler();
               return $handler->render($output);
            } else {
               throw new ApplicationError("Invalid handler '$handler'");
            }
         } else {
            return $output;
         }
      }
   }

?>
