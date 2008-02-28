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
         foreach (array(VIEWS, LIB.'views') as $base) {
            if (is_file($template = "$base/$path.thtml")) {
               return $template;
            }
         }

         return null;
      }

      public $data;

      private $template;
      private $layout;

      function __construct($template=null, $layout=null) {
         $this->template = $template;
         $this->layout = $layout;
      }

      function set($key, $value) {
         $this->data[$key] = $value;
      }

      function get_template() {
         return $this->template;
      }

      function set_template($template) {
         $this->template = $template;
      }

      function get_layout() {
         return $this->layout;
      }

      function set_layout($layout) {
         $this->layout = $layout;
      }

      function render($template=null, $layout=null) {
         if (!$template and !$template = $this->template) {
            raise("No template set");
         } elseif (substr(basename($template), 0, 1) == '_') {
            raise("Template '$template' is a partial");
         } elseif (!is_file($template)) {
            if (is_file($file = View::find_template($template))) {
               $template = $file;
            } else {
               raise(new MissingTemplate("Template '{$template}' not found"));
            }
         }

         if ($layout or !is_null($layout = $this->layout)
             and !is_file($layout) and $layout) {
            if (is_file($file = View::find_template("layouts/$layout"))) {
               $layout = $file;
            } else {
               raise("Layout '{$layout}' not found");
            }
         }

         $this->template = $template;
         $this->layout = $layout;

         # Reset cycler (from text_helper.php)
         $GLOBALS['_cycle'] = null;

         # Extract assigned values as local variables
         extract($this->data, EXTR_SKIP);

         # Render the template
         ob_start();
         require $template;
         $content_for_layout = ob_get_clean();
         log_debug("Rendered template {$template}");

         if (is_file($layout)) {
            # Render the layout
            ob_start();
            require $layout;
            $output = ob_get_clean();
            log_debug("Rendered layout {$layout}");
         } else {
            $output = $content_for_layout;
         }

         return $output;
      }

      private function render_partial($partial, $locals=null) {
         $partial = dirname($partial).'/_'.basename($partial).'.thtml';
         if (!$template = View::find_template($partial) and
             !$template = View::find_template(VIEWS.basename($partial))) {
            raise("Partial not found: $partial");
         }

         # Extract assigned and passed values as local variables
         extract($this->data, EXTR_SKIP);
         if (is_array($locals)) {
            extract($locals, EXTR_SKIP);
         }

         # Render the partial
         ob_start();
         require $template;
         $output = ob_get_clean();
         log_debug("Rendered partial $template");

         return $output;
      }
   }

?>
