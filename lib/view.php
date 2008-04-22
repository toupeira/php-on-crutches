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
         $base = '{'.VIEWS.','.LIB.'views/}';

         # Look for templates with a language suffix first (e.g. index.en.thtml)
         if ($lang = config('language')) {
            $lang = '{.'.config('language').',}';
         }

         # Return the first match
         return array_shift(glob("$base$path$lang.thtml", GLOB_BRACE));
      }

      public $template;
      public $layout;

      protected $data;
      protected $partial;
      protected $locals;

      function __construct($template=null, $layout=null) {
         $this->template = $template;
         $this->layout = $layout;
      }

      function get_data() {
         return (array) $this->data;
      }

      # Get and set template values
      function get($key) {
         return $this->data[$key];
      }

      function set($key, $value) {
         $this->data[$key] = &$value;
         return $this;
      }

      # Render a template
      function render($template=null, $layout=null) {
         if (!$this->template and !$this->template = $template) {
            throw new ApplicationError("No template set");
         } elseif (!is_file($this->template)) {
            if (is_file($file = View::find_template($this->template))) {
               $this->template = $file;
            } else {
               throw new MissingTemplate("Template '{$this->template}.thtml' not found");
            }
         }

         # Discard local variables to avoid conflicts with assigned template variables
         # (keep $layout to allow overriding inside the template)
         unset($template);
         unset($file);

         # Extract assigned values as local variables
         if (extract((array) $this->data, EXTR_SKIP) != count($this->data)) {
            throw new ApplicationError("Couldn't extract all template variables");
         }

         # Render the template
         ob_start();
         require $this->template;
         $content_for_layout = ob_get_clean();
         log_debug("Rendered template {$this->template}");

         if (!is_null($layout)) {
            $this->layout = $layout;
         }

         if ($this->layout and !is_file($this->layout)) {
            if (is_file($_file = View::find_template("layouts/{$this->layout}"))) {
               $this->layout = $_file;
               unset($_file);
            } else {
               throw new MissingTemplate("Layout '{$this->layout}' not found");
            }
         }

         if (is_file($this->layout)) {
            # Render the layout
            ob_start();
            require $this->layout;
            $output = ob_get_clean();
            log_debug("Rendered layout {$this->layout}");
         } else {
            $output = $content_for_layout;
         }

         return $output;
      }

      # Render a partial template
      protected function render_partial($partial, $locals=null) {
         if (strstr($partial, '/') !== false) {
            $partial = dirname($partial).'/_'.basename($partial);
         } else {
            $partial = substr(dirname($this->template), strlen(VIEWS)).'/_'.$partial;
         }

         if (!$this->partial = View::find_template($partial) and
             !$this->partial = View::find_template(VIEWS.basename($partial))) {
            throw new ApplicationError("Partial '$partial' not found");
         }

         $this->locals = $locals;

         # Discard local variables to avoid conflicts with assigned template variables
         unset($partial);
         unset($locals);

         # Extract assigned values as local variables
         if (extract((array) $this->data, EXTR_SKIP) != count($this->data)) {
            throw new ApplicationError("Couldn't extract all template variables");
         }

         # Extract passed values as local variables
         if (is_array($this->locals) and extract((array) $this->locals, EXTR_SKIP) != count($this->locals)) {
            throw new ApplicationError("Couldn't extract all passed locales");
         }

         # Render the partial
         ob_start();
         require $this->partial;
         $output = ob_get_clean();
         log_debug("Rendered partial {$this->partial}");

         return $output;
      }
   }

?>
