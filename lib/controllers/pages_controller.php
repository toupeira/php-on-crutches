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
         # Catch path traversal attacks
         $path = str_replace('..', '', trim($this->params['id'], '/'));

         if ($template = View::find_template("pages/$path") or
             $template = View::find_template("pages/$path/index")) {
            $this->set('title', str_replace('/', ' - ', $path));
            $this->render($template);
         } else {
            throw new MissingTemplate("Template 'pages/{$path}.thtml' not found");
         }
      }
   }

?>
