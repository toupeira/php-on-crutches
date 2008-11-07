<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Start the session if necessary
   function session_handler_start() {
      if (!session_id() and is_object($GLOBALS['_SESSION_STORE'])) {
         $GLOBALS['_SESSION_STORE']->start();
      }
   }

   abstract class SessionStore
   {
      function setup() { return true; }
      function open($save_path, $session_name) { return true; }
      function close() { return true; }
      function expire($maxlifetime) { }

      function start() {
         session_start();

         # Override default cache headers
         header('Cache-Control: private');
         header('Pragma: cache');

         # Make sure the session handler can clean up
         register_shutdown_function(session_write_close);
      }

      abstract function read($id);
      abstract function write($id, $data);
      abstract function destroy($id);
   }

   class SessionStoreCache extends SessionStore
   {
      function read($id) {
         return (string) cache("session_$id");
      }

      function write($id, $data) {
         cache_set("session_$id", $data);
         return true;
      }

      function destroy($id) {
         return cache_expire("session_$id");
      }
   }

   /*
   class SessionStoreCookie extends SessionStore
   {
   }

   class SessionStoreDatabase extends SessionStore
   {
   }
   */

?>
