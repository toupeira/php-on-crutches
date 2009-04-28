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
      protected $_valid_methods = true;

      function is_valid_request($action) {
         return true;
      }

      function show($exception) {
         if ($exception == 400 or $exception instanceof InvalidRequest) {
            $status = 400;
            $text = 'Bad Request';
         } elseif ($exception == 403 or $exception instanceof AccessDenied) {
            $status = 403;
            $text = 'Forbidden';
         } elseif ($exception == 404 or $exception instanceof NotFound) {
            $status = 404;
            $text = 'Not Found';
         } else {
            $status = 500;
            $text = 'Internal Server Error';
         }

         $this->headers['Status'] = $status;
         $this->send_headers(true);

         if (config('debug') and $this->is_trusted() and $exception instanceof Exception) {
            $this->debug($exception);
         } else {
            if (View::find_template("errors/$status")) {
               $this->render($status);

               # Pad the output to at least 512 bytes so IE will always display the custom error page
               $this->_output = str_pad($this->_output, 512, ' ');
            } else {
               $this->render_text("<h1>$status $text</h1>");
            }
         }

         return $this->_output;
      }

      function debug($exception=null, $expand=false) {
         if (!$exception instanceof Exception) {
            throw new NotFound();
         }

         $class = get_class($exception);
         $message = preg_replace(
            "/('[^']+'|[^ ]+\(\))/",
            '<code>$1</code>',
            $exception->getMessage()
         );
         $file = $exception->getFile();
         $line = $exception->getLine();
         $trace = $exception->getTraceAsString();

         try {
            $this->set('exception', $class);
            $this->set('message', $message);
            $this->set('file', str_replace(ROOT, '', $file));
            $this->set('line', $line);
            $this->set('trace', $trace);

            $this->set('expand', $expand);

            # Get source code where the error occurred
            if (is_file($file)) {
               $code = '';
               $start = max(0, $line - 12);
               $lines = array_slice(file($file), $start, 23);
               $width = strlen($line + 23);

               foreach ($lines as $i => $text) {
                  $i += $start + 1;
                  $text = sprintf("%{$width}d %s", $i, htmlspecialchars($text));
                  if ($i == $line) {
                     # Highlight the line with the error
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
            while (ob_get_level()) {
               ob_end_clean();
            }

            return "<h2>".titleize($class).": $message</h2>\n"
                 . "<p>in <strong><code>$file</code></strong> at line <strong>$line</strong></p>\n"
                 . "<pre>$trace</pre>\n"
                 . "<br><br>\n"
                 . "Additionaly, the following internal error occured while trying to handle the exception:\n"
                 . "<h2>".titleize(get_class($e)).': '.$e->getMessage()."</h2>\n"
                 . "<p>in <strong><code>".$e->getFile()."</code></strong> at line <strong>".$e->getLine()."</strong></p>\n"
                 . "<pre>".$e->getTraceAsString()."</pre>";

         }
      }
   }

?>
