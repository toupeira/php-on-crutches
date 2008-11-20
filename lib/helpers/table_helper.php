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
      foreach ((array) $items as $title => $content) {
         if (is_array($content)) {
            $values = array();
            foreach ($content as $key => $value) {
               if (is_numeric($key)) {
                  $values[] = $value;
               } else {
                  $values[] = "$key: $value";
               }
            }
            $content = implode(', ', $values);
         }

         if ($escape) {
            $title = h($title);
            $content = h($content);
         }
         $html .= "<tr><th>$title</th><td>$content</td></tr>";
      }

      return content_tag('table', $html, $options);
   }

?>
