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

   # Base class for all objects
   class Object
   {
      # Automatic getters
      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } else {
            $class = get_class($this);
            throw new ApplicationError("Call to undefined method $class::$getter()");
         }
      }

      # Automatic setters
      function __set($key, $value) {
         $setter = "set_$key";
         if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
         } else {
            $class = get_class($this);
            throw new ApplicationError("Call to undefined method $class::$setter()");
         }
      }

      # Call a function if it is defined
      function call_if_defined($method) {
         if (method_exists($this, $method)) {
            return $this->$method();
         }
      }

      # Call a function if it is defined, and raise an exception if it returns false
      function call_filter($filter) {
         if ($this->call_if_defined($filter) === false) {
            throw new ApplicationError("Filter '$filter' returned false");
         }
      }
   }

   # Standard errors
   class StandardError extends Exception {
      function __construct($message=null, $code=0, $file=null, $line=null) {
         parent::__construct($message, $code);
         ($file and $this->file = $file);
         ($line and $this->line = $line);
      }
   }

   class ApplicationError extends StandardError {};
   class ConfigurationError extends ApplicationError {};
   class NotFound extends ApplicationError {};
   class RoutingError extends NotFound {};
   class MissingTemplate extends NotFound {};

   # Return the first non-empty value. Basically a workaround for
   # PHP's broken || operator, which only returns booleans.
   function any() {
      foreach (func_get_args() as $arg) {
         if ($arg) {
            return $arg;
         }
      }
   }

   # Create something almost, but not quite, entirely unlike a real closure.
   #
   # $code is a string with the function body, and $argc is the number of
   # arguments the function receives. The arguments are named alphabetically,
   # i.e. $a, $b, $c etc.
   #
   # Use it like this:
   #
   #   $square = proc('$a * $a')
   #   $square(2) # -> 4
   #
   # Or in the real-world, to sort an array of people by age:
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

   # Check if a string is empty or only contains whitespace.
   function blank($text) {
      $text = trim($text);
      return empty($text);
   }

?>
