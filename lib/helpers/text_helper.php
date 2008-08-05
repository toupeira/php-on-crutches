<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function h($text, $double_encode=false) {
      return htmlspecialchars($text, ENT_COMPAT, 'UTF-8', $double_encode);
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

   function is_email($text) {
      return preg_match('/^[\w._%+-]+@([\w.-]+\.)+[a-z]{2,6}$/i', $text);
   }

   function auto_link($text) {
      return preg_replace_callback(AUTO_LINK_URLS_PATTERN, auto_link_urls, $text);
   }

   # Shamelessly stolen from Rails
   define_default('AUTO_LINK_URLS_PATTERN', '{
      (                                               # leading text
         <\w+.*?>|                                    # leading HTML tag, or
         [^=!:\'"/]|                                  # leading punctuation, or
         ^                                            # beginning of line
      )
      (
         (?:https?://)|                               # protocol spec, or
         (?:www\.)                                    # www.*
      )
      (
         [-\w]+                                       # subdomain or domain
         (?:\.[-\w]+)*                                # remaining subdomains or domain
         (?::\d+)?                                    # port
         (?:/(?:(?:[~\w\+@%-]|(?:[,.;:][^\s$]))+)?)*  # path
         (?:\?[\w\+@%&=.;-]+)?                        # query string
         (?:\#[\w\-]*)?                               # trailing anchor
      )
      ([[:punct:]]|\s|<|$)                            # trailing text
   }x');

   function auto_link_urls($match) {
      list($all, $a, $b, $c, $d) = $match;
      if (preg_match('/<a\s/i', $match[1])) {
         return $all;
      } else {
         return "$a<a href=\"".h(($b == 'www.' ? 'http://www.' : $b).$c)."\">".h($b.$c)."</a>$d";
      }
   }

   define_default('FORMAT_TIME', '%Y-%m-%d %T');
   define_default('FORMAT_DATE', '%Y-%m-%d');

   define_default('FORMAT_DB_TIME', '%Y-%m-%d %T');
   define_default('FORMAT_DB_DATE', '%Y-%m-%d');

   function format_time($time, $format=FORMAT_TIME) {
      if (!is_numeric($time)) {
         $time = strtotime($time);
      }

      if ($time) {
         return strftime($format, $time);
      }
   }

   define_default('KB', 1024);
   define_default('MB', 1024 * KB);
   define_default('GB', 1024 * MB);

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

   define_default('MINUTE', 60);
   define_default('HOUR',   60 * MINUTE);
   define_default('DAY',    24 * HOUR);
   define_default('WEEK',    7 * DAY);
   define_default('MONTH',  30 * DAY);
   define_default('YEAR',  365 * DAY);

   function indent($text, $indent=2) {
      return preg_replace('/^/m', str_repeat(' ', $indent), $text);
   }

   function br2nl($text) {
      return str_replace("<br />", "\n", $text);
   }

   function cycle($values) {
      static $_cycle;

      $values = func_get_args();
      $value = $values[intval($_cycle)];
      if (++$_cycle >= count($values)) {
         $_cycle = 0;
      }
      return $value;
   }

   function colorize($text) {
      return strtr(str_replace('[0;', '[1;', $text), array(
         '[1;31m' => '<strong style="color: red">',
         '[1;32m' => '<strong style="color: green">',
         '[1;33m' => '<strong style="color: yellow">',
         '[1;34m' => '<strong style="color: blue">',
         '[1;35m' => '<strong style="color: purple">',
         '[1;36m' => '<strong style="color: cyan">',
         '[1m' => '<strong style="color: white">',
         '[0m' => '</strong>',
      ));
   }

?>
