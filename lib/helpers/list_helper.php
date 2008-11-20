<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function list_tag(array $items, array $options=null, array $item_options=null) {
      $escape = (bool) $options['escape'];

      $html = '';
      foreach ((array) $items as $key => $value) {
         if (is_array($value)) {
            $content = (is_string($key) ? $key : '');
            if (is_string($key)) {
               $content = ($escape ? h($key) : $key);
            } else {
               $content = '';
            }

            $content .= list_tag($value, $options, $item_options);
         } else {
            $content = ($escape ? h($value) : $value);
         }

         $html .= content_tag('li', $content, $item_options);
      }

      unset($options['escape']);
      $type = any(array_delete($options, 'type'), 'ul');
      return content_tag($type, $html, $options);
   }

?>
