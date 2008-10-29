<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Dump a value
   function dump($value, $colored=false) {
      $output = trim(print_r($value, true));

      if (is_array($value)) {
         $lines = array_slice(explode("\n", $output), 2, -1);
         $output = array();
         foreach ($lines as $line) {
            $output[] = mb_substr($line, 4);
         }
         $output = implode("\n", $output);
      }

      if ($colored) {
         return syntax_highlight($output);
      } else {
         return '<pre>'.h($output).'</pre>';
      }
   }

   function dump_colored($value) {
      ob_start();
      var_dump($value);
      $output = ob_get_clean();

      if (is_array($value)) {
         $lines = explode("\n", $output);
         $output = '<pre>';
         foreach (array_slice($lines, 2, -1) as $line) {
            $output .= substr($line, 2)."\n";
         }
         $output .= '</pre>';
      }

      return $output;
   }

   # Dump an exception with colored backtrace
   function dump_exception($exception) {
      $dump = "[1;31m".get_class($exception)."[0m: [1m".$exception->getMessage()."[0m\n";

      foreach (explode("\n", $exception->getTraceAsString()) as $line) {
         list($line, $text) = explode(' ', $line, 2);
         $dump .= "   [1m".$line."[0m ".$text."\n";
      }

      return $dump;
   }

?>
