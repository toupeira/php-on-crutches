<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function session($key, $value=null) {
      if ($value) {
         return $_SESSION[$key] = $value;
      } else {
         return $_SESSION[$key];
      }
   }

   abstract class SessionStore
   {
      function setup() { return true; }
      function open($save_path, $session_name) { return true; }
      function close() { return true; }
      function expire($maxlifetime) { }

      abstract function read($id);
      abstract function write($id, $data);
      abstract function delete($id);
   }

   class SessionStoreCache extends SessionStore
   {
      function read($id) {
         #log_info("READING SESSION: $id\n");
         return (string) cache("session_$id");
      }

      function write($id, $data) {
         #log_info("WRITING SESSION: $id\n");
         cache("session_$id", $data);
         return true;
      }

      function delete($id) {
         #log_info("DESTROYING SESSION: $id\n");
         return cache()->delete("session_$id");
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
