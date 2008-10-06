<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function table_tag(array $items, array $options=null) {
      $html = '';
      foreach ((array) $items as $key => $value) {
         $html .= "<tr><th>$key</th><td>$value</td></tr>";
      }

      return content_tag('table', $html, $options);
   }

?>
