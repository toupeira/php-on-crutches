<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function session($key, $value=null) {
      return $_SESSION[$key];
   }

   function session_set($key, $value) {
      return $_SESSION[$key] = $value;
   }

   abstract class SessionStore
   {
      function setup() { return true; }
      function open($save_path, $session_name) { return true; }
      function close() { return true; }
      function expire($maxlifetime) { }

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
