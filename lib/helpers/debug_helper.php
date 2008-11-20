<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Dump a formatted value
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

   # Dump a raw value
   function dump_value($value) {
      if (is_numeric($value)) {
         return $value;
      } elseif (is_array($value)) {
         return trim(print_r($value, true));
      } elseif (is_object($value)) {
         if (method_exists($value, 'inspect')) {
            return $value->inspect();
         } elseif (method_exists($value, '__toString') and !$value instanceof Exception) {
            return $value->__toString();
         } else {
            return get_class($value);
         }
      } elseif (is_resource($value)) {
         ob_start();
         var_dump($value);
         return trim(ob_get_clean());
      } else {
         return var_export($value, true);
      }
   }

   # Dump function information
   function dump_function($function) {
      $dump = "\n  ";

      try {
         $reflect = new ReflectionFunction($function);
      } catch (Exception $e) {
         $dump .= "Function [1m".$function."()[0m does not exist\n\n";
         return $dump;
      }

      $dump .= "[1m".$function." ([0m ";
      $signatures = array();
      foreach ($reflect->getParameters() as $param) {
         $signature = '$'.$param->getName();
         if ($param->isPassedByReference()) {
            $signature = "&$signature";
         }
         if ($param->isDefaultValueAvailable()) {
            $signature .= "=".dump_value($param->getDefaultValue());
         }
         if ($param->isOptional()) {
            $signature = "[0;32m[ ".$signature." ][0m";
         } else {
            $signature = "[0;36m".$signature."[0m";
         }
         $signatures[] = $signature;
      }

      $dump .= implode(', ', $signatures);
      $dump .= " [1m)[0m\n\n";

      return $dump;
   }

   # Dump an exception with colored backtrace
   function dump_exception($exception) {
      $dump = "[1;31m".get_class($exception)."[0m: [1m".$exception->getMessage()."[0m\n";

      if ($file = $exception->getFile() and $line = $exception->getLine()) {
         $dump .= " in [1m$file[0m at line [1m$line[0m\n";
      }

      foreach (explode("\n", $exception->getTraceAsString()) as $line) {
         list($line, $text) = explode(' ', $line, 2);
         $dump .= "   [1m".$line."[0m ".$text."\n";
      }

      return $dump;
   }

?>
