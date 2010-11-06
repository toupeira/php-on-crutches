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

   function strip_html($text) {
      return html_entity_decode(strip_tags($text), ENT_COMPAT, 'UTF-8');
   }

   # Truncate text to a given length. If $add_title is set, the full text
   # will be added as a tooltip.
   function truncate($text, $max_length=40, $add_title=false, $add='...') {
      if (mb_strlen($text) > ceil($max_length)) {
         $truncated = rtrim(mb_substr($text, 0, round($max_length)));
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
            $size = sprintf(any($format, '%d'), round($size / KB));
         }
      } elseif ($size < GB) {
         $text = _("%s MB");
         $size = sprintf(any($format, '%.1f'), round($size / MB, 1));
      } else {
         $text = _("%s GB");
         $size = sprintf(any($format, '%.2f'), round($size / GB, 1));
      }

      return sprintf($text, $size);
   }

   function to_time($time) {
      if (is_numeric($time)) {
         return $time;
      } elseif ($time !== '0000-00-00' and $time !== '0000-00-00 00:00:00') {
         return strtotime($time);
      }
   }

   define_default('FORMAT_TIME', '%Y-%m-%d %T');
   define_default('FORMAT_DB_TIME', '%Y-%m-%d %T');

   function format_time($time=null, $format=FORMAT_TIME) {
      $format = _($format);
      if (is_null($time)) {
         return strftime($format);
      } elseif ($time = to_time($time)) {
         return strftime($format, $time);
      }
   }

   define_default('FORMAT_DATE', '%Y-%m-%d');
   define_default('FORMAT_DB_DATE', '%Y-%m-%d');

   function format_date($date=null, $format=FORMAT_DATE) {
      return format_time($date, $format);
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

   function highlight($text, $pattern) {
      $pattern = preg_quote($pattern);
      return preg_replace("/($pattern)/", '<strong>\1</strong>', $text);
   }

   function simple_format($text) {
      return str_replace("\n", "<br />\n", h($text));
   }

   function is_email($email) {
      return preg_match("/^.+@[^\.].+\.[a-z]{2,6}$/i", $email) > 0;
   }

   function is_reachable_email($email) {
      return @dns_get_mx($domain = array_pop(explode('@', $email, 2)), $mx) or
             @dns_get_record($domain, DNS_A);
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

   function br2nl($text) {
      return strtr($text, array(
         '<br />' => "\n",
         '<br>'   => "\n",
      ));
   }

   function cycle($values=null, $reset=false) {
      static $_cycle;

      if ($reset) {
         $_cycle = 0;
      }

      if (func_num_args()) {
         $values = func_get_args();
      } else {
         $values = array('odd', 'even');
      }

      $value = $values[round($_cycle)];

      if (++$_cycle >= count($values)) {
         $_cycle = 0;
      }

      return $value;
   }

   function value_changed($value, $reset=false) {
      static $_value;
      static $_first_run = true;

      if ($reset) {
         $_first_run = true;
      }

      if ($_first_run) {
         $_first_run = false;
         $_value = !$value;
      }

      if ($value === $_value) {
         return false;
      } else {
         $_value = $value;
         return true;
      }
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
