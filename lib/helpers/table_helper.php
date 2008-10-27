<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function table_tag(array $items, array $options=null) {
      $escape = true;
      if (isset($options['escape'])) {
         $escape = $options['escape'];
         unset($options['escape']);
      }

      $html = '';
      foreach ((array) $items as $key => $value) {
         if ($escape) {
            $key = h($key);
            $value = h($value);
         }
         $html .= "<tr><th>$key</th><td>$value</td></tr>";
      }

      return content_tag('table', $html, $options);
   }

?>
