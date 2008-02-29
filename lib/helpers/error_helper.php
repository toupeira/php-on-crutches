<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Handler for PHP errors
   function error_handler($errno, $errstr, $errfile, $errline) {
      throw new StandardError($errstr, $errno, $errfile, $errline);
   }

   # Handler for uncaught exceptions
   function exception_handler($exception) {
      if ($exception instanceof NotFound) {
         $status = 404;
         $text = "Not Found";
      } else {
         $status = 500;
         $text = "Server Error";
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

   # Dump a value
   function dump($value) {
      ob_start();
      print_r($value);
      $output = htmlspecialchars(ob_get_clean());
      if (is_array($value)) {
         $output = implode("\n", array_slice(explode("\n", $output), 1));
      }

      return "<pre>$output</pre>";
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
