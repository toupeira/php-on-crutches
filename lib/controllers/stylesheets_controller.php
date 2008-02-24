<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class StylesheetsController extends ApplicationController
   {
      function before() {
         if (is_file($php = STYLESHEETS.basename($this->get('action')).'.php')) {
            ob_start();
            require $php;
            $output = ob_get_clean();

            $css = substr($php, 0, strlen($php) - 4);
            file_put_contents($css, $output);

            $this->send_file($css, array('inline' => true));
         } else {
            raise(MissingTemplate);
         }
      }
   }

?>
