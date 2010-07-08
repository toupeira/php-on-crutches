<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Base class for all custom exceptions
   class StandardError extends ErrorException {}

   # PHP exceptions
   class SyntaxError extends StandardError {}
   class RuntimeError extends StandardError {}
   class FatalError extends RuntimeError {}

   class ValueError extends StandardError {
      function __construct($value, $message=null) {
         if ($message) {
            $message = sprintf($message, $value);
         } else {
            $message = "Invalid value '$value'";
         }

         parent::__construct($message);
      }
   }

   class TypeError extends StandardError {
      function __construct($value, $message=null) {
         $type = (is_object($value) ? get_class($value) : gettype($value));
         if ($message) {
            $message = sprintf($message, $type);
         } else {
            $message = "Invalid argument '$value' of type '$type'";
         }

         parent::__construct($message);
      }
   }

   class UndefinedMethod extends StandardError {
      function __construct($class, $method) {
         if (is_object($class)) {
            $class = get_class($class);
         }

         parent::__construct("Call to undefined method $class#$method()");
      }
   }

?>
