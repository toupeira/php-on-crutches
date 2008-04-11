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

   function simple_format($text) {
      return str_replace("\n", "<br />", h($text));
   }

   function auto_link($text) {
      return preg_replace_callback('#\b(\w+://[^\s]+)#',
         proc('link_to(h($a[1]), h($a[1]))'), $text);
   }

   function br2nl($text) {
      return str_replace("<br />", "\n", $text);
   }

   define('KB', 1024);
   define('MB', 1024 * KB);
   define('GB', 1024 * MB);

   function format_size($size) {
      if ($size < KB) {
         return "$size Bytes";
      } elseif ($size < MB) {
         return sprintf('%d KB', $size / KB);
      } elseif ($size < GB) {
         return sprintf('%d MB', $size / MB);
      } else {
         return sprintf('%d GB', $size / GB);
      }
   }

   function format_date($time, $format='%d.%m.%y') {
      if ($time) {
         return strftime($format, strtotime($time));
      }
   }

   function format_time($time, $format='%d.%m.%Y %T') {
      if ($time) {
         return strftime($format, strtotime($time));
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

?>
