<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

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

?>
