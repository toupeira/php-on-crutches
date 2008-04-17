<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function h($text) {
      return strtr(htmlspecialchars($text, ENT_COMPAT, 'UTF-8'), array(
         '&amp;gt;'   => '&gt;',
         '&amp;lt;'   => '&lt;',
         '&amp;amp;'  => '&amp;',
         '&amp;quot;' => '&quot;',
      ));
   }

   function strip_html($text) {
      return html_entity_decode(strip_tags($text), ENT_COMPAT, 'UTF-8');
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
      return str_replace("\n", "<br />\n", h($text));
   }

   function auto_link($text) {
      return preg_replace_callback('#\b(\w+://[-\.\w]+(/[^\s]*)?)#',
         proc('link_to(h($a[1]), h($a[1]))'), $text);
   }

   function format_date($time, $format='%d.%m.%y') {
      if ($time) {
         return strftime($format, strtotime($time));
      }
   }

   function format_time($time, $format='%d.%m.%Y %H:%M') {
      if ($time) {
         return strftime($format, strtotime($time));
      }
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

   function indent($text, $indent=2) {
      return preg_replace('/^/m', str_repeat(' ', $indent), $text);
   }

   function br2nl($text) {
      return str_replace("<br />", "\n", $text);
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
