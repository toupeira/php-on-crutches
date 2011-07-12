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
      return $text[0] == $search[0]
         and (isset($search[1]) ? substr($text, 0, mb_strlen($search)) === $search
                                : true);
   }

   # Search for a substring at the end
   function ends_with($text, $search) {
      $len_text = mb_strlen($text);
      $len_search = mb_strlen($search);

      return $text[$len_text - 1] == $search[$len_search - 1]
         and $len_search <= $len_text
         and ($len_search > 1 ? substr($text, $len_text - $len_search) === $search
                              : true);
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
      return is_string($string) and mb_detect_encoding($string, "UTF-8, $charsets, ASCII", true) == "UTF-8";
   }

   # Convert a string to UTF8 if necessary
   function to_utf8($string, $charset="ISO-8859-15") {
      if (is_utf8($string)) {
         return $string;
      } else {
         return @iconv($charset, "UTF-8//TRANSLIT//IGNORE", $string);
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
      if (is_object($data) and method_exists($data, 'to_json')) {
         return $data->to_json();
      } else {
         return json_encode($data);
      }
   }

   function to_xml($data=null) {
      if (is_object($data) and method_exists($data, 'to_xml')) {
         return $data->to_xml();
      } else {
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
   }

?>
