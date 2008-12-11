<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function is_post() {
      return Dispatcher::$controller->is_post();
   }

   function is_ajax() {
      return Dispatcher::$controller->is_ajax();
   }

   function is_ssl() {
      return Dispatcher::$controller->is_ssl();
   }

   function is_trusted($action=null) {
      return Dispatcher::$controller->is_trusted($action);
   }

?>
