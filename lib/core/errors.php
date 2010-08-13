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
   class RuntimeError extends StandardError {
      function __construct($message, $code=null, $severity=null, $filename=null, $lineno=null) {
         if ($severity == E_STRICT) {
            $message = "Strict Error: $message";
         }

         parent::__construct($message, $code, $severity, $filename, $lineno);
      }
   }

   class FatalError extends RuntimeError {}
   class SyntaxError extends RuntimeError {}

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
