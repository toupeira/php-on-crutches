<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class ErrorsController extends Controller
   {
      function is_valid_request($action) {
         return true;
      }

      function show($exception) {
         $this->headers['Status'] = $status;

         if (config('debug') and $exception instanceof Exception) {
            $this->show_debug($exception);
         } else {
            if ($exception == 400 or $exception instanceof InvalidRequest) {
               $status = 400;
               $text = 'Bad Request';
            } elseif ($exception == 404 or $exception instanceof NotFound) {
               $status = 404;
               $text = 'Not Found';
            } else {
               $status = 500;
               $text = 'Internal Server Error';
            }

            if (View::find_template("errors/$status")) {
               $this->render($status);
            } else {
               $this->render_text("<h1>$status $text</h1>");
            }
         }
      }

      function show_debug($exception, $expand=false) {
         if (!$exception instanceof Exception) {
            throw new NotFound();
         }

         $class = get_class($exception);
         $file = $exception->getFile();
         $line = $exception->getLine();
         $message = preg_replace("/('[^']+'|[^ ]+\(\))/", '<code>$1</code>', $exception->getMessage());
         $trace = $exception->getTraceAsString();

         try {
            $this->set('exception', $class);
            $this->set('message', $message);
            $this->set('trace', $trace);
            $this->set('file', str_replace(ROOT, '', $file));
            $this->set('line', $line);
            $this->set('params', Dispatcher::$params);
            $this->set('expand', $expand);

            if (is_file($file)) {
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
               $this->set('code', $code);
            }

            $this->_output = null;
            $this->render('debug', '');
            return $this->_output;

         } catch (Exception $e) {
            return $e->getMessage();
            ob_end_clean();
            return "<h1>".titleize($class)."</h1>\n"
               . "<p>$message, in <code>$file</code> at line $line</p>\n"
               . "<pre>$trace</pre>";

         }
      }
   }

?>
