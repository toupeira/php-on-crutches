<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   if (!function_exists('is_logged_in')) {
      function is_logged_in() {
         return ApplicationController::is_logged_in();
      }
   }

   if (!function_exists('is_admin')) {
      function is_admin() {
         return ApplicationController::is_admin();
      }
   }

?>
