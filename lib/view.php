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
      # Find a template for the given path
      static function find_template($path) {
         # Look in /app/views first, then in /lib/views
         $views = '{'.VIEWS.','.LIB.'views/}';

         # Look for templates with a language suffix first (e.g. index.en.thtml)
         if ($lang = config('language')) {
            $lang = '{.'.config('language').',}';
         }

         # Return the first match
         return array_shift(glob("$views$path$lang.thtml", GLOB_BRACE));
      }

      protected $_template;
      protected $_layout;
      protected $_data;

      protected $_partial;
      protected $_locals;

      function __construct($template=null, $layout=null) {
         $this->_template = $template;
         $this->_layout = $layout;
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

         Dispatcher::$render_time -= microtime(true);

         # Discard local variables to avoid conflicts with assigned template variables
         # (keep $layout to allow overriding inside the template)
         unset($template);
         unset($file);

         # Provide a reference to the HTML builder
         $html = HtmlBuilder::instance();

         # Extract assigned values as local variables
         if (extract((array) $this->_data, EXTR_SKIP) != count($this->_data)) {
            throw new ApplicationError("Couldn't extract all template variables");
         }

         # Render the template
         log_info(sprintf('Rendering '.str_replace(VIEWS, '', $this->_template)));
         ob_start();
         require $this->_template;
         $content_for_layout = ob_get_clean();

         if (!is_null($layout)) {
            $this->_layout = $layout;
         }

         if ($this->_layout and !is_file($this->_layout)) {
            if (is_file($_file = View::find_template("layouts/{$this->_layout}"))) {
               $this->_layout = $_file;
               unset($_file);
            } else {
               throw new MissingTemplate("Layout '{$this->_layout}' not found");
            }
         }

         if (is_file($this->_layout)) {
            # Render the layout
            log_info('Rendering template within '.str_replace(VIEWS, '', $this->_layout));
            ob_start();
            require $this->_layout;
            $output = ob_get_clean();
         } else {
            $output = $content_for_layout;
         }

         Dispatcher::$render_time += microtime(true);

         return $output;
      }

      # Render a partial template
      function render_partial($partial, array $locals=null) {
         if (strstr($partial, '/') !== false) {
            $partial = dirname($partial).'/_'.basename($partial);
         } else {
            $partial = substr(dirname($this->_template), strlen(VIEWS)).'/_'.$partial;
         }

         if (!$this->_partial = View::find_template($partial) and
             !$this->_partial = View::find_template(VIEWS.basename($partial))) {
            throw new ApplicationError("Partial '$partial' not found");
         }

         $this->_locals = $locals;

         # Discard local variables to avoid conflicts with assigned template variables
         unset($partial);
         unset($locals);

         # Extract assigned values as local variables
         if (extract((array) $this->_data, EXTR_SKIP) != count($this->_data)) {
            throw new ApplicationError("Couldn't extract all template variables");
         }

         # Extract passed values as local variables
         if (is_array($this->_locals) and extract((array) $this->_locals, EXTR_SKIP) != count($this->_locals)) {
            throw new ApplicationError("Couldn't extract all passed locales");
         }

         # Render the partial
         log_info("Rendering partial {$this->_partial}");
         ob_start();
         require $this->_partial;
         $output = ob_get_clean();

         return $output;
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
   }

?>
