<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class SessionStore
   {
      static function setup() { return true; }
      static function open($save_path, $session_name) { return true; }
      static function close() { return true; }
      static function expire($maxlifetime) { }

      abstract static function read($id);
      abstract static function write($id, $data);
      abstract static function destroy($id);
   }

   class SessionStoreCache extends SessionStore
   {
      static function read($id) {
         return (string) cache("session_$id");
      }

      static function write($id, $data) {
         cache_set("session_$id", $data);
         return true;
      }

      static function destroy($id) {
         return cache_expire("session_$id");
      }
   }

   class SessionStoreMysql extends SessionStore
   {
      static protected $_id;
      static protected $_data;

      static function read($id) {
         self::$_id = $id;

         return self::$_data = (string) DB()->execute(
            'SELECT data FROM sessions WHERE id = ?', $id
         )->fetch_column();
      }

      static function write($id, $data) {
         if ($id == self::$_id and $data == self::$_data) {
            return;
         }

         if ($model = config('auth_model')) {
            if ($user = call_user_func(array($model, 'current'))) {
               $user = $user->id;
            }

            $key = foreign_key($model);

            DB()->execute(
               "INSERT INTO sessions (id, $key, data)"
               . " VALUES (?, ?, ?)"
               . " ON DUPLICATE KEY UPDATE $key = ?, data = ?",
               $id, $user, $data, $user, $data
            );
         } else {
            DB()->execute(
               'INSERT INTO sessions (id, data)'
               . ' VALUES (?, ?)'
               . ' ON DUPLICATE KEY UPDATE data = ?',
               $id, $data, $data
            );
         }
      }

      static function destroy($id) {
         DB()->execute(
            'DELETE FROM sessions WHERE id = ?', $id
         );
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
