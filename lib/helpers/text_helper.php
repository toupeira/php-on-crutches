<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require LIB.'vendor/textile.php';

   function h($text, $double_encode=false) {
      try {
         return htmlspecialchars($text, ENT_COMPAT, 'UTF-8', $double_encode);
      } catch (StandardError $e) {
         # Catch invalid Unicode characters
         return htmlspecialchars($text, ENT_COMPAT, null, $double_encode);
      }
   }

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

   function strip_html($text) {
      return html_entity_decode(strip_tags($text), ENT_COMPAT, 'UTF-8');
   }

   # Truncate text to a given length. If $add_title is set, the full text
   # will be added as a tooltip.
   function truncate($text, $length=40, $add_title=false, $add='...') {
      if (strlen($text) > $length) {
         $truncated = rtrim(mb_substr($text, 0, $length));
         if ($add_title) {
            return '<span title="'.h($text).'">'.h($truncated).$add.'</span>';
         } else {
            return $truncated.$add;
         }
      } else {
         if ($add_title) {
            return '<span>'.h($text).'</span>';
         } else {
            return $text;
         }
      }
   }

   function simple_format($text) {
      return str_replace("\n", "<br />\n", h($text));
   }

   function is_email($text) {
      return preg_match("/^.+@.+\.[a-z]{2,6}$/i", $text) > 0;
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

   function format_date($date, $format=FORMAT_DATE) {
      if ($date = to_time($date)) {
         return strftime(_($format), $date);
      }
   }

   function format_time($time, $format=FORMAT_TIME) {
      if ($time = to_time($time)) {
         return strftime(_($format), $time);
      }
   }

   define_default('KB', 1024);
   define_default('MB', 1024 * KB);
   define_default('GB', 1024 * MB);

   function format_size($size, $format=null) {
      if ($size < KB) {
         $text = _("1 KB");
      } elseif ($size < MB) {
         $text = _("%s KB");
         $size = sprintf(any($format, '%d'), $size / KB);
      } elseif ($size < GB) {
         $text = _("%s MB");
         $size = sprintf(any($format, '%.1f'), $size / MB);
      } else {
         $text = _("%s GB");
         $size = sprintf(any($format, '%.2f'), $size / GB);
      }

      return sprintf($text, $size);
   }

   define_default('MINUTE', 60);
   define_default('HOUR',   60 * MINUTE);
   define_default('DAY',    24 * HOUR);
   define_default('WEEK',    7 * DAY);
   define_default('MONTH',  30 * DAY);
   define_default('YEAR',  365 * DAY);

   function to_time($time) {
      return is_numeric($time) ? $time : strtotime($time);
   }

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

   function indent($text, $indent=2) {
      return preg_replace('/^/m', str_repeat(' ', $indent), $text);
   }

   function br2nl($text) {
      return str_replace("<br />", "\n", $text);
   }

   function cycle($values=null) {
      static $_cycle;

      if ($values === false) {
         $_cycle = 0;
         return;
      } elseif ($values) {
         $values = func_get_args();
      } else {
         $values = array('odd', 'even');
      }

      $value = $values[intval($_cycle)];

      if (++$_cycle >= count($values)) {
         $_cycle = 0;
      }

      return $value;
   }

   function textilize($text) {
      static $_textile;
      if (!$_textile) $_textile = new Textile();
      return $_textile->process($text);
   }

   function syntax_highlight($code, $lang='php') {
      $code = trim($code);
      switch ($lang) {
         case 'php':
         case 'sql':
            return strtr(highlight_string("<? $code ?>", true), array(
               '&lt;?&nbsp;' => '',
               '?&gt;'       => '',
            ));
         default:
            throw new NotImplemented("Unsupported language '$lang'");
      }
   }

   function colorize($text) {
      return strtr(str_replace('[0;', '[1;', $text), array(
         '[1;31m' => '<strong style="color: red">',
         '[1;32m' => '<strong style="color: green">',
         '[1;33m' => '<strong style="color: orange">',
         '[1;34m' => '<strong style="color: darkblue">',
         '[1;35m' => '<strong style="color: purple">',
         '[1;36m' => '<strong style="color: blue">',
         '[1m' => '<strong>',
         '[0m' => '</strong>',
      ));
   }

   function hexencode($text) {
      for ($i = 0; $i < mb_strlen($text); $i++) {
         $encoded .= '&#'.ord($text[$i]).';';
      }

      return $encoded;
   }

   function generate_password($vowels=3, $consonants=2, $uppercase=1, $numbers=1, $dots=1) {
      $password = '';

      $v = array('a', 'e', 'i', 'o', 'u');
      $c = array('b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x');

      for ($i = 0; $i < $vowels; $i++) {
         $password .= $v[mt_rand(0, count($v) - 1)];
      }

      for ($i = 0; $i < $consonants; $i++) {
         $password .= $c[mt_rand(0, count($c) - 1)];
      }

      for ($i = 0; $i < $uppercase; $i++) {
         $password .= chr(mt_rand(65, 90));
      }

      for ($i = 0; $i < $numbers; $i++) {
         $password .= mt_rand(0, 9);
      }

      for ($i = 0; $i < $dots; $i++) {
         $password .= '.';
      }

      return str_shuffle($password);
   }

?>
