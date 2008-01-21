<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class PagesController extends ApplicationController
   {
      function show($path) {
         if ($template = self::find_template("pages/$path") or
             $template = self::find_template("pages/$path/index")) {
            $this->render($template);
         } else {
            raise(MissingTemplate);
         }
      }
   }

?>
