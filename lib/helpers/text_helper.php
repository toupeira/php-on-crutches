<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function h($text) {
      return htmlentities($text);
   }

   function pluralize($count, $singular, $plural) {
      return $count == 1 ? "$count $singular" : "$count $plural";
   }

   function humanize($text) {
      return ucfirst(str_replace('_', ' ', underscore($text)));
   }

   function camelize($text) {
      $text = str_replace('_', ' ', $text);
      for ($i = 0; $i < strlen($text); $i++) {
         if ($text[$i] == ' ') {
            $text[$i+1] = strtoupper($text[$i+1]);
         }
      }
      return str_replace(' ', '', ucfirst($text));
   }

   function underscore($text) {
      return strtolower(preg_replace('/([a-z]) ?([A-Z])/', '\1_\2',
                                     basename($text)));
   }

   function truncate($text, $length=40) {
      if (strlen($text) > $length) {
         return substr($text, 0, $length)."...";
      } else {
         return $text;
      }
   }

   function cycle($values) {
      global $_cycle;
      $values = func_get_args();
      $value = $values[intval($_cycle)];
      if (++$_cycle >= count($values)) {
         $_cycle = 0;
      }
      return $value;
   }

   function br2nl($text) {
      return str_replace("<br />", "\n", $text);
   }

?>
