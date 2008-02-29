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
      $output = htmlspecialchars(ob_get_clean());
      if (is_array($value)) {
         $output = implode("\n", array_slice(explode("\n", $output), 1));
      }

      return "<pre>$output</pre>";
   }

   # Dump an exception with backtrace.
   # Returns the formatted string.
   function dump_error($exception) {
      $class = get_class($exception);
      $file = $exception->getFile();
      $line = $exception->getLine();
      $message = preg_replace("/('[^']+')/", '<code>$1</code>', $exception->getMessage());
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
         $lines = array_slice(file($file), max(0, $line - 12), 23);
         $width = strlen($line + 12);
         foreach ($lines as $i => $text) {
            $text = sprintf("%{$width}d %s", $i + $line - 11, htmlspecialchars($text));
            if ($i == 11) {
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
