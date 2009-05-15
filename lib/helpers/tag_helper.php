<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function tag($name, array $options=null, array $defaults=null) {
      return build_tag($name, $options, $defaults)." />";
   }

   function content_tag($name, $content=null, array $options=null, array $defaults=null) {
      if ($content === false or $options['open'] or $defaults['open']) {
         $open = true;
         unset($options['open']);
         unset($defaults['open']);
      }

      $html = build_tag($name, $options, $defaults).">$content";
      if (!$open) {
         $html .= "</$name>";
      }

      return $html;
   }

   function build_tag($name, array $options=null, array $defaults=null) {
      $options = array_merge(
         (array) $defaults,
         (array) $options
      );

      if (!array_delete($options, 'force_class')
            and $name == 'input'
            and isset($options['type'])
            and $options['type'] != 'hidden')
      {
         $options['class'] .= ' '.$options['type'];
         if (in_array($options['type'], array('submit', 'reset'))) {
            $options['class'] .= ' button';
         } elseif ($options['type'] == 'password') {
            $options['class'] .= ' text';
         }
      } elseif ($name == 'textarea') {
         $options['class'] .= ' text';
      }

      if ($options['class'] !== null) {
         $options['class'] = trim($options['class']);
      }

      $html = "<$name";
      foreach ($options as $option => $value) {
         if ($value !== null and $value !== false) {
            if ($value === true) {
               # Support boolean tags, e.g. 'disabled', 'checked' etc.
               $value = $option;
            }

            $html .= " $option=\"".h($value)."\"";
         }
      }

      return $html;
   }

   class HtmlBuilder
   {
      static protected $_instance;

      static function instance() {
         if (self::$_instance) {
            return self::$_instance;
         } else {
            return self::$_instance = new HtmlBuilder();
         }
      }

      static function compile($_template, $_locals) {
         $html = self::instance();

         # Extract assigned values as local variables
         if (extract((array) $_locals, EXTR_SKIP) != count($_locals)) {
            throw new ApplicationError("Couldn't extract all template variables");
         }

         ob_start();
         require $_template;
         return ob_get_clean();
      }

      private function __construct() {}

      function __call($method, $args=null) {
         $tag = strtolower($method);
         $options = is_array($args[0]) ? array_shift($args) : null;
         $children = $args;

         if ($children) {
            $indent = "\n  ";
            $children = str_replace("\n", $indent, implode("\n", $children));
            if ($children and preg_match('/\n/', $children)) {
               $children = "$indent$children\n";
            }
            return content_tag($tag, $children, $options);
         } else {
            return tag($tag, $options);
         }
      }
   }

?>
