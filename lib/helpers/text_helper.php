<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function h($text) {
      return htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
   }

   function pluralize($count, $singular, $plural) {
      return $count == 1 ? "$count $singular" : "$count $plural";
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
