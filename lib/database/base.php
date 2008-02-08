<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require LIB.'database/connection.php';
   require LIB.'database/active_record.php';

   abstract class DB
   {
      static private $connection;
      static private $models;

      static function connect($database) {
         return self::$connection = DatabaseConnection::load($database);
      }

      static function query($sql) {
         if (empty($sql)) return null;

         if (!self::$connection) {
            self::connect('default');
         }

         $args = func_get_args();
         return call_user_func_array(array(self::$connection, query), $args);
      }

      static function create($class, $attributes) {
         $model = new $class($attributes);
         if ($model->save()) {
            return $model;
         }
      }

      static function find($class) {
         if (empty($class)) return null;
         $args = func_get_args();
         return self::delegate(find, $args);
      }

      static function find_all($class) {
         if (empty($class)) return null;
         $args = func_get_args();
         return self::delegate(find_all, $args);
      }

      static private function delegate($method, $args) {
         $class = $args[0];
         if (!is_object($model = self::$models[$class])) {
            if (class_exists($class)) {
               $model = self::$models[$class] = new $class();
            } else {
               raise("Invalid model '$class'");
            }
         }

         return call_user_func_array(array($model, "_$method"), array_slice($args, 1));
      }
   }

?>
