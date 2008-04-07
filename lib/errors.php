<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Base class for custom exceptions
   class StandardError extends Exception {
      # Allow passing filename and line number
      function __construct($message=null, $code=0, $file=null, $line=null) {
         parent::__construct($message, $code);
         ($file and $this->file = $file);
         ($line and $this->line = $line);
      }
   }

   # Standard exceptions
   class ApplicationError extends StandardError {};
   class NotImplemented extends ApplicationError {};
   class ConfigurationError extends ApplicationError {};
   class NotFound extends ApplicationError {};
   class RoutingError extends NotFound {};
   class MissingTemplate extends NotFound {};

   # Handler for PHP errors
   function error_handler($errno, $errstr, $errfile, $errline) {
      if (error_reporting()) {
         throw new StandardError($errstr, $errno, $errfile, $errline);
      }
   }

   # Handler for uncaught exceptions
   function exception_handler($exception) {
      if (log_running()) {
         log_error("\n".get_class($exception).': '.$exception->getMessage());
         log_debug("  ".str_replace("\n", "\n  ", $exception->getTraceAsString()));
      }

      if ($exception instanceof NotFound) {
         $status = 404;
         $text = "Not Found";
      } else {
         $status = 500;
         $text = "Server Error";
      }

      while (ob_get_level()) {
         ob_end_clean();
      }

      header("Status: $status");

      if (config('debug')) {
         print render_exception($exception);
      } elseif ($template = View::find_template("errors/$status")) {
         $view = new View($template);
         print $view->render();
      } else {
         print "<h1>$status $text</h1>";
      }
   }

   # Render an exception with stack trace
   function render_exception($exception) {
      $class = get_class($exception);
      $file = $exception->getFile();
      $line = $exception->getLine();
      $message = preg_replace("/('[^']+'|[^ ]+\(\))/", '<code>$1</code>', $exception->getMessage());
      $trace = $exception->getTraceAsString();

      try {
         $view = new View('errors/trace');
         $view->set('exception', $class);
         $view->set('message', $message);
         $view->set('trace', $trace);

         $view->set('file', str_replace(ROOT, '', $file));
         $view->set('line', $line);
         $view->set('params', Dispatcher::$params);

         $code = '';
         $start = max(0, $line - 12);
         $lines = array_slice(file($file), $start, 23);
         $width = strlen($line + 23);

         foreach ($lines as $i => $text) {
            $i += $start + 1;
            $text = sprintf("%{$width}d %s", $i, htmlspecialchars($text));
            if ($i == $line) {
               $text = "<strong>$text</strong>";
            }
            $code .= $text;
         }
         $view->set('code', $code);

         return $view->render();
      } catch (Exception $e) {
         return "<h1>".titleize($class)."</h1>\n<p>$message</p>\n<pre>$trace</pre>";
      }
   }

?>
