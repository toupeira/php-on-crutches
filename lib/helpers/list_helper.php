<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function list_tag($items, $options=null, $item_options=null) {
      $html = '';
      foreach ((array) $items as $key => $value) {
         if (is_array($value)) {
            $content = (is_string($key) ? $key : '');
            $content .= list_tag($value, $options, $item_options);
         } else {
            $content = $value;
         }
         $html .= content_tag('li', $content, $item_options);
      }

      $type = any(array_delete($options, 'type'), 'ul');
      return content_tag($type, $html, $options);
   }

?>
