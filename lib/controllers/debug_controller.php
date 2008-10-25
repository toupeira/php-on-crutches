<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class DebugController extends Controller
   {
      function is_valid_request($action) {
         if (Dispatcher::$controller instanceof DebugController) {
            return parent::is_valid_request($action);
         } else {
            return true;
         }
      }

      function toolbar($check=null) {
         # This action can only be performed internally
         if (!($check === true)) {
            throw new NotFound();
         }

         $this->view->layout = '';
      }
   }

?>
