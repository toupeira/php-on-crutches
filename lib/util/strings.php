<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Shorthand for newlines
   define('N', "\n");

   # Search for a substring at the beginning
   function starts_with($text, $search) {
      return substr($text, 0, mb_strlen($search)) === $search;
   }

   # Search for a substring at the end
   function ends_with($text, $search) {
      return substr($text, mb_strlen($text) - mb_strlen($search)) === $search;
   }

   # Match a literal string or a regex
   function match($pattern, $value) {
      if ($pattern[0] == '/' and mb_substr($pattern, mb_strlen($pattern) - 1) == '/') {
         return preg_match($pattern, $value);
      } else {
         return $value === $pattern;
      }
   }

   # Indent each line in the string
   function indent($text, $indent=2) {
      return preg_replace('/^/m', str_repeat(' ', $indent), $text);
   }

   # Check if a string is UTF8-encoded
   function is_utf8($string, $charsets="ISO-8859-15") {
      return is_string($string) and mb_detect_encoding($string, "UTF-8, $charsets, ASCII") == "UTF-8";
   }

   # Convert a string to UTF8 if necessary
   function to_utf8($string, $charset="ISO-8859-15") {
      if (is_utf8($string)) {
         return $string;
      } else {
         return iconv($charset, "UTF-8//TRANSLIT//IGNORE", $string);
      }
   }

   # Convert any value to a (hopefully) usable string form
   function to_string($value) {
      if (is_object($value)) {
         if (method_exists($value, '__toString') and !$value instanceof Exception) {
            return $value->__toString();
         } else {
            return get_class($value);
         }
      } elseif (is_array($value)) {
         return trim(print_r($value, true));
      } elseif (is_resource($value)) {
         ob_start();
         var_dump($value);
         return trim(ob_get_clean());
      } else {
         return var_export($value, true);
      }
   }

   function to_json($data) {
      return json_encode($data);
   }

   function to_xml($data=null) {
      $data = is_array($data) ? $data : func_get_args();

      $xml = '';
      foreach ($data as $key => $value) {
         if (is_array($value)) {
            $value = to_xml($value);
         } else {
            $value = h(to_string($value));
         }

         $xml .= content_tag($key, $value);
      }

      return $xml;
   }

   define_default('FORMAT_DATE', '%Y-%m-%d');
   define_default('FORMAT_DB_DATE', '%Y-%m-%d');

   function format_date($date, $format=FORMAT_DATE) {
      if ($date = to_time($date)) {
         return strftime(_($format), $date);
      }
   }

   define_default('FORMAT_TIME', '%Y-%m-%d %T');
   define_default('FORMAT_DB_TIME', '%Y-%m-%d %T');

   function format_time($time, $format=FORMAT_TIME) {
      if ($time = to_time($time)) {
         return strftime(_($format), $time);
      }
   }

   function format_number($number, $decimals=0, $dec_point='.', $thousands_sep="'") {
      return number_format($number, $decimals, $dec_point, $thousands_sep);
   }

   define('KB', 1024);
   define('MB', 1024 * KB);
   define('GB', 1024 * MB);

   function format_size($size, $format=null) {
      if ($size < MB) {
         $text = _("%s KB");
         if ($size <= 0) {
            $size = 0;
         } elseif ($size < KB) {
            $size = 1;
         } else {
            $size = sprintf(any($format, '%d'), $size / KB);
         }
      } elseif ($size < GB) {
         $text = _("%s MB");
         $size = sprintf(any($format, '%.1f'), $size / MB);
      } else {
         $text = _("%s GB");
         $size = sprintf(any($format, '%.2f'), $size / GB);
      }

      return sprintf($text, $size);
   }

   function to_time($time) {
      if (is_numeric($time)) {
         return $time;
      } elseif (!in_array($time, array('0000-00-00', '0000-00-00 00:00:00'))) {
         return strtotime($time);
      }
   }

   define('MINUTE', 60);
   define('HOUR',   60 * MINUTE);
   define('DAY',    24 * HOUR);
   define('WEEK',    7 * DAY);
   define('MONTH',  30 * DAY);
   define('YEAR',  365 * DAY);

   function format_duration($then, $now=null) {
      $seconds = any(to_time($now), time()) - to_time($then);

      if ($seconds < MINUTE) {
         $time = $seconds;
         $text = ngettext("%d second", "%d seconds", $time);
      } elseif ($seconds < HOUR) {
         $time = $seconds / MINUTE;
         $text = ngettext("%d minute", "%d minutes", $time);
      } elseif ($seconds < DAY) {
         $time = $seconds / HOUR;
         $text = ngettext("%d hour", "%d hours", $time);
      } elseif ($seconds < WEEK) {
         $time = $seconds / DAY;
         $text = ngettext("%d day", "%d days", $time);
      } elseif ($seconds < MONTH) {
         $time = $seconds / MONTH;
         $text = ngettext("%d month", "%d months", $time);
      } else {
         $time = $seconds / YEAR;
         $text = ngettext("%d year", "%d years", $time);
      }

      return sprintf($text, $time);
   }

?>
