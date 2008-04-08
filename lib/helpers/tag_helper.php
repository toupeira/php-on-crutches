<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function build_tag($name, $options, $defaults=null) {
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
         if ($value !== null) {
            $value = str_replace('"', '\"', $value);
            $html .= " $option=\"$value\"";
         }
      }

      return $html;
   }

   function tag($name, $options=null, $defaults=null) {
      return build_tag($name, $options, $defaults)." />";
   }

   function content_tag($name, $content=null, $options=null, $defaults=null) {
      $open = any(
         array_delete($options, 'open'),
         array_delete($defaults, 'open')
      );

      $html = build_tag($name, $options, $defaults).">$content";
      if (!$open) {
         $html .= "</$name>";
      }
      return $html;
   }

?>
