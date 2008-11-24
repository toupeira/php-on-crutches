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
            $message = "Invalid argument of type '$type'";
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

   # Framework exceptions
   class ApplicationError extends StandardError {}
   class NotImplemented extends ApplicationError {}
   class ConfigurationError extends ApplicationError {}
   class MailerError extends ApplicationError {}

   class NotFound extends ApplicationError {}
   class RoutingError extends NotFound {}
   class MissingTemplate extends NotFound {}

   class InvalidRequest extends ApplicationError {
      function __construct($message) {
         parent::__construct("Invalid request for this action: $message");
      }
   }

   class AccessDenied extends ApplicationError {
      function __construct($message) {
         parent::__construct("Access denied for this action: $message");
      }
   }

   # Handler for PHP errors
   function error_handler($errno, $errstr, $errfile, $errline) {
      if (error_reporting()) {
         throw new RuntimeError($errstr, 0, $errno, $errfile, $errline);
      }
   }

   # Catch "fatal" PHP errors
   function fatal_error_handler() {
      if ($handler = config('exception_handler') and $error = error_get_last()) {
         if ((error_reporting() & $error['type']) == 0) {
            return;
         } elseif ($error['type'] == 4) {
            $exception = SyntaxError;
         } else {
            $exception = FatalError;
         }

         $handler(new $exception(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
         ));
      }
   }

   # Handler for uncaught exceptions
   function exception_handler($exception) {
      while (ob_get_level()) {
         ob_end_clean();
      }

      if (PHP_SAPI == 'cli') {
         print dump_exception($exception)."\n";
      } else {
         if (log_running()) {
            log_msg("\n".dump_exception($exception), $exception instanceof NotFound ? LOG_INFO : LOG_ERROR);
         }

         Dispatcher::$controller = new ErrorsController();
         print Dispatcher::$controller->show($exception);

         if (log_level(LOG_INFO)) {
            Dispatcher::log_footer();
         }

         if ($notify = config('notify_exceptions') and !$exception instanceof NotFound) {
            $mail = new Mail();
            $mail->subject = get_class($exception);
            $mail->alt_body = dump_exception($exception);
            $mail->body = Dispatcher::$controller->show_debug($exception, true);
            $mail->content_type = 'text/html';

            foreach ((array) $notify as $address) {
               $mail->add_address($address);
            }

            $mail->send();
         }
      }

      exit(1);
   }


?>
