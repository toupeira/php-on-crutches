<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Shorthand for newlines
   define(N, "\n");

   # Return the first non-empty value
   function any() {
      foreach (func_get_args() as $arg) {
         if ($arg) {
            return $arg;
         }
      }
   }

   # Check if a string is empty or only contains whitespace
   function blank($text) {
      $text = trim($text);
      return empty($text);
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

   # Set fake request information, useful for testing and the console
   function fake_request($path=null, $method='GET', $ssl=false) {
      $_SERVER['HTTP_HOST'] = 'www.example.com';
      $_SERVER['REQUEST_URI'] = "/$path";
      $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
      $_SERVER['REQUEST_METHOD'] = $method;
      $_SERVER['HTTPS'] = $ssl ? 'on' : null;
   }

?>
