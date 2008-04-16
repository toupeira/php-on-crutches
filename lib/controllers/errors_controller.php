<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class ErrorsController extends ApplicationController
   {
      function show($status) {
         if (ctype_digit($status) and $template = View::find_template("errors/$status")) {
            $this->render($status);
         } else {
            print "<h1>$status $text</h1>";
         }
      }
   }

?>
