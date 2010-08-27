<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Framework exceptions
   class ApplicationError extends StandardError {}
   class NotImplemented extends ApplicationError {}
   class ConfigurationError extends ApplicationError {}
   class MailerError extends ApplicationError {}

   class NotFound extends ApplicationError {}
   class RoutingError extends NotFound {}
   class MissingTemplate extends NotFound {}

   class DebugTrace extends Exception {}

   class InvalidRequest extends ApplicationError {
      function __construct($message=null) {
         if ($message) {
            $message = ": $message";
         }

         parent::__construct("Invalid request for this action$message");
      }
   }

   class AccessDenied extends ApplicationError {
      function __construct($message=null) {
         if ($message) {
            $message = ": $message";
         }

         parent::__construct("Access denied for this action$message");
      }
   }

   class ServiceUnavailable extends ApplicationError {
      function __construct($message=null) {
         if ($message) {
            $message = ": $message";
         }

         parent::__construct("Service currently not available$message");
      }
   }

   class ModelError extends ApplicationError {
      function __construct($object, $message=null) {
         $model = get_class($object);
         parent::__construct(sprintf(
            any($message, 
               "Couldn't save %s instance\n"
               . "  Errors: %s\n"
               . "  Attributes: %s\n"
            ),
            get_class($object),
            array_to_str($object->errors),
            array_to_str($object->attributes)
         ));
      }
   }

   class ClientError extends ApplicationError {
      protected $_trace;

      function __construct($message, $file, $line, $trace=null) {
         parent::__construct($message, 0, E_ERROR, $file, intval($line));
         $this->_trace = $trace;
      }

      function getClientTrace() { return $this->_trace; }
   }

   # Global variable to track if an exception was caught
   $_EXCEPTION_CAUGHT = false;

   # Handler for PHP errors
   function error_handler($errno, $errstr, $errfile, $errline) {
      # Don't throw exceptions for PHP errors after an exception was already caught,
      # to avoid "Exception thrown without a stack frame" errors
      if (error_reporting() and !$GLOBALS['_EXCEPTION_CAUGHT'] and !ignore_php_error($errstr)) {
         $exception = new RuntimeError($errstr, 0, $errno, $errfile, $errline);
         if ($errno == E_NOTICE) {
            log_exception($exception);
         } else {
            throw $exception;
         }
      }
   }

   # Catch "fatal" PHP errors by registering this as a shutdown function.
   # Gets the last error and passes it to the exception handler.
   function fatal_error_handler() {
      if ($handler = config('exception_handler') and $error = error_get_last()) {
         if ((error_reporting() & $error['type']) == 0 or ignore_php_error($error['message'])) {
            # Ignore errors hidden by PHP's settings
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
      if ($GLOBALS['_EXCEPTION_CAUGHT']) {
         return;
      } else {
         $GLOBALS['_EXCEPTION_CAUGHT'] = true;
      }

      # Clear the output buffer
      while (ob_get_level()) {
         ob_end_clean();
      }

      log_exception($exception);

      if (PHP_SAPI != 'cli') {
         # Generate an error report from a controller
         Dispatcher::$controller = new ErrorsController();
         print Dispatcher::$controller->show($exception);

         if (log_running() and log_level(LOG_INFO)) {
            # Log the request footer if necessary
            Dispatcher::log_footer(true);
         }

         send_error_notification($exception);
      }

      exit(1);
   }

   # Add a debug trace to be logged later if an exception occurs
   function log_trace($msg) {
      $GLOBALS['_TRACE'][] = new DebugTrace($msg);
   }

   # Log an exception, with request header if necessary
   function log_exception($exception, $info=false) {
      if (log_running()) {
         if (!is_object($exception)) {
            $exception = new DebugTrace($exception);
         }

         $dump = "\n".dump_exception($exception);

         if (ignore_exception($exception)) {
            log_info($dump);
         } else {
            if (!$info and !log_level(LOG_INFO) and PHP_SAPI != 'cli') {
               # Log the request header if necessary
               Dispatcher::log_header(
                  get_class(Dispatcher::$controller),
                  Dispatcher::$params['action'],
                  true
               );
            }

            if ($info) {
               $logger = 'log_info';
            } else {
               $logger = 'log_error';
            }

            # Log any debug traces
            foreach ((array) $GLOBALS['_TRACE'] as $trace) {
               $logger(dump_exception($trace));
            }

            $logger($dump);
         }
      }
   }

   # Send an error notification mail to the specified recipients,
   # or the currently configured default recipients
   function send_error_notification($exception, $recipients=null) {
      $recipients = any(
         $recipients, config('notify_errors')
      );

      if (!is_object($exception)) {
         $exception = new DebugTrace($exception);
      }

      if ($recipients and !ignore_exception($exception) and !ignore_notification($exception)) {
         $mail = new Mail();
         $mail->content_type = 'text/html';

         # Generate the HTML body from a controller
         $controller = new ErrorsController();
         $mail->body = $controller->debug($exception);
         $mail->subject = strtoupper(trim(`hostname -s`)).": ".$controller->get('title');

         # Add a plain text dump of the exception
         $mail->alt_body = dump_exception($exception);

         foreach ((array) $recipients as $address) {
            $mail->add_address($address);
         }

         $mail->send();
      }
   }

   # Check if a PHP error should be ignored
   function ignore_php_error($message) {
      if (is_array($errors = config('ignore_php_errors'))) {
         foreach ($errors as $error => $length) {
            if ($error[0] == $message[0] and $error == substr($message, 0, $length)) {
               return true;
            }
         }
      }

      return false;
   }

   # Check if an exception should be ignored
   function ignore_exception($exception) {
      foreach ((array) config('ignore_errors') as $class) {
         # Use instanceof to also catch inherited classes
         if ($exception instanceof $class) {
            return true;
         }
      }

      return false;
   }

   # Check if an exception should be notified
   function ignore_notification($exception) {
      foreach ((array) config('ignore_notifications') as $class) {
         # Use instanceof to also catch inherited classes
         if ($exception instanceof $class) {
            return true;
         }
      }

      return false;
   }

?>
