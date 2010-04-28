<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Check if a value is empty
   function blank($value) {
      if ($value instanceof QuerySet) {
         return $value->empty;
      } elseif (is_string($value)) {
         $value = trim($value);
         return empty($value) and $value !== '0';
      } else {
         return empty($value) and $value !== 0;
      }
   }

   # Require a file if it exists
   function try_require($files) {
      if (!is_array($files)) {
         $files = func_get_args();
      }

      foreach ($files as $file) {
         if (is_file($file)) {
            require_once $file;
            return true;
         }
      }
   }

   # Define a constant if it isn't set yet
   function define_default($constant, $value) {
      if (!defined($constant)) {
         define($constant, $value);
      }
   }

   # Return the first non-empty value
   function any() {
      foreach (func_get_args() as $arg) {
         if ($arg) {
            return $arg;
         }
      }
   }

   # Apply a callback with multiple argument values, and collect the return values in an array
   function apply($callback, $values=null) {
      $values = array_slice(func_get_args(), 1);
      $count = max(array_map(count, $values));

      $return = array();
      for ($i = 0; $i < $count; $i++) {
         $args = array();
         foreach ($values as $value) {
            $args[] = is_array($value) ? $value[$i] : $value;
         }

         $return[] = call_user_func_array($callback, $args);
      }

      return $return;
   }

   # A wrapper around create_function()'s horrible syntax
   #
   # $code is a string with the function body, and $argc is the number of
   # arguments the function receives. The arguments are named alphabetically,
   # i.e. $a, $b, $c etc.
   #
   # Use it like this:
   #
   #   $square = proc('$a * $a')
   #   $square(2) # -> 4
   #   $square(3) # -> 9
   #   $square(4) # -> 16
   #
   # Or to sort an array of people by age, for example:
   #
   #   usort($people, proc('$a->age > $b->age', 2))
   #
   function proc($code, $argc=1) {
      $args = array();
      $argc = min(26, $argc);
      for ($i = 0; $i < $argc; $i++) {
         $args[] = '$'.chr(97 + $i);
      }
      $args = implode(',', $args);
      return create_function($args, "return $code;");
   }

?>
