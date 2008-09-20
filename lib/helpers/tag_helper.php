<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

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

   function tag($name, array $options=null, array $defaults=null) {
      return build_tag($name, $options, $defaults)." />";
   }

   function content_tag($name, $content=null, array $options=null, array $defaults=null) {
      if ($options['open']) {
         $open = true;
         unset($options['open']);
      } elseif ($defaults['open']) {
         $open = true;
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

      if ($name == 'input' and isset($options['type']) and $options['type'] != 'hidden') {
         $options['class'] .= ' '.$options['type'];
         if (in_array($options['type'], array('submit', 'reset'))) {
            $options['class'] .= ' button';
         } elseif (in_array($options['type'], array('password', 'file'))) {
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

?>
