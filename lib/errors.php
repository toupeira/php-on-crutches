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

   # Framework exceptions
   class ApplicationError extends StandardError {}
   class NotImplemented extends ApplicationError {}
   class ConfigurationError extends ApplicationError {}
   class MailerError extends ApplicationError {}
   class NotFound extends ApplicationError {}
   class RoutingError extends NotFound {}
   class MissingTemplate extends NotFound {}

   class TypeError extends StandardError {
      function __construct($value) {
         $type = (is_object($value) ? get_class($value) : gettype($value));
         parent::__construct("Invalid argument of type '$type'");
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

   # Handler for PHP errors
   function error_handler($errno, $errstr, $errfile, $errline) {
      if (error_reporting()) {
         throw new StandardError($errstr, 0, $errno, $errfile, $errline);
      }
   }

   # Handler for uncaught exceptions
   function exception_handler($exception) {
      if (log_running()) {
         log_error("\n".dump_exception($exception));
      }

      while (ob_get_level()) {
         ob_end_clean();
      }

      Dispatcher::$controller = new ErrorsController();
      print Dispatcher::$controller->perform('show', array($exception));

      if (log_level(LOG_INFO)) {
         Dispatcher::log_footer();
      }

      exit;
   }


?>
