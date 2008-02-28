<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Dump a value
   function dump($value) {
      ob_start();
      print_r($value);
      $output = ob_get_clean();
      if (is_array($value)) {
         $output = implode("\n", array_slice(explode("\n", $output), 1));
      }

      return "<pre>$output</pre>";
   }

   # Dump an exception with backtrace.
   # Returns the formatted string.
   function dump_error($exception) {
      $class = titleize(get_class($exception));
      $message = preg_replace("/('[^']+')/", '<code>$1</code>', $exception->getMessage());
      $trace = $exception->getTraceAsString();

      try {
         $view = new View('errors/trace');
         $view->set('exception', $class);
         $view->set('message', $message);
         $view->set('trace', $trace);
         $view->set('params', Dispatcher::$params);
         return $view->render();
      } catch (Exception $e) {
         return "<h1>".titleize($class)."</h1>\n<p>$message</p>\n<pre>$trace</pre>";
      }
   }

?>
