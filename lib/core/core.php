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
            raise("Call to undefined method $class::$getter()");
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
            raise("Call to undefined method $class::$setter()");
         }
      }

      # Call a function if it is defined
      function call_if_defined($method) {
         if (method_exists($this, $method)) {
            return $this->$method();
         }
      }
   }

   # Raise an exception.
   #
   # $exception can be an instantiated exception, an exception class,
   # or a string with an error message.
   #
   function raise($exception, $log=true) {
      if ($exception instanceof Exception) {
         $message = get_class($exception);
      } elseif (class_exists($exception)) {
         $exception = new $exception();
         $message = get_class($exception);
      } else {
         $message = $exception;
         $exception = new ApplicationError($message);
      }

      if ($log and log_running()) {
         log_error("\n".get_class($exception).": $message");
         log_debug("  ".str_replace("\n", "\n  ", $exception->getTraceAsString()));
      }

      throw $exception;
   }

   # Standard errors
   class ApplicationError extends Exception {};
   class MissingTemplate extends ApplicationError {};

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

   # Assert the number or types of the given arguments array.
   function assert_args($args, $rule) {
      if (is_numeric($rule)) {
         if (count($args) != $rule) {
            raise("Expected $rule arguments, got ".count($args));
         }
      } elseif (is_array($rule)) {
         foreach ($rule as $i => $type) {
            if (gettype($args[$i]) != $type) {
               raise("Expected $type for argument ".($i + 1).", got ".gettype($args[$i]));
            }
         }
      } else {
         raise("Invalid rules given");
      }
   }

?>
