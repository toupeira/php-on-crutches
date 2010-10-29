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

      static function read($id)         { throw new NotImplemented(); }
      static function write($id, $data) { throw new NotImplemented(); }
      static function destroy($id)      { throw new NotImplemented(); }
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
      static protected $_database;

      static function setup() {
         self::$_database = any(config('session_store_database', 'default'));
         return true;
      }

      static function read($id) {
         self::$_id = $id;

         try {
            return self::$_data = (string) DB(self::$_database)->execute(
               'SELECT data FROM sessions WHERE id = ?', $id
            )->fetch_column();
         } catch (Exception $e) {
            if (config('debug')) {
               throw $e;
            } elseif (log_running()) {
               log_exception($e);
            } else {
               error_log(dump_exception($e));
            }
         }
      }

      static function write($id, $data) {
         if ($id == self::$_id and $data == self::$_data) {
            return;
         }

         try {
            if ($model = config('auth_model')) {
               if ($user = call_user_func(array($model, 'current'))) {
                  $user = $user->id;
               }

               $key = foreign_key($model);

               DB(self::$_database)->execute(
                  "INSERT INTO sessions (id, $key, data)"
                  . " VALUES (?, ?, ?)"
                  . " ON DUPLICATE KEY UPDATE $key = ?, data = ?",
                  $id, $user, $data, $user, $data
               );
            } else {
               DB(self::$_database)->execute(
                  'INSERT INTO sessions (id, data)'
                  . ' VALUES (?, ?)'
                  . ' ON DUPLICATE KEY UPDATE data = ?',
                  $id, $data, $data
               );
            }
         } catch (Exception $e) {
            if (config('debug')) {
               throw $e;
            } elseif (log_running()) {
               log_exception($e);
            } else {
               error_log(dump_exception($e));
            }
         }
      }

      static function destroy($id) {
         try {
            DB(self::$_database)->execute(
               'DELETE FROM sessions WHERE id = ?', $id
            );
         } catch (Exception $e) {
            if (config('debug')) {
               throw $e;
            } elseif (log_running()) {
               log_exception($e);
            } else {
               error_log(dump_exception($e));
            }
         }
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
