<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class DatabaseMapper extends Object
   {
      static protected $mappers;

      static function load($model) {
         $class = is_object($model) ? get_class($model) : $model;
         if ($mapper = self::$mappers[$class]) {
            return $mapper;
         } else {
            return self::$mappers[$class] = new DatabaseMapper($model);
         }
      }

      protected $model;
      protected $connection;
      protected $database;
      protected $table;

      protected function __construct($model) {
         if (is_object($model)) {
            $this->model = get_class($model);
         } else {
            $model = new $model();
            $this->model = $model;
         }

         if (! $model instanceof ActiveRecord) {
            raise("Invalid model '".get_class($this)."'");
         }

         $this->database = $model->database;
         $this->table = $model->table;
      }

      function get_connection() {
         if (!$this->connection) {
            $this->connection = DatabaseConnection::load($this->database);
         }

         return $this->connection;
      }

      function get_attributes() {
         return $this->get_connection()->get_attributes($this->table);
      }

      function query() {
         $args = func_get_args();
         return call_user_func_array(array($this->get_connection(), query), $args);
      }

      function create($attributes) {
         $model = new $this->model($attributes);
         return $model->save();
      }

      function insert($attributes) {
         $keys = array();
         $values = array();

         foreach ($attributes as $key => $value) {
            $keys[] = '?';
            $values[] = $value;
         }

         $query = sprintf("INSERT INTO `%s` VALUES (%s)",
                          $this->table, implode(", ", $keys));
         $this->query($query, $values);

         return $this->connection->insert_id();
      }

      function update($id, $attributes) {
         if (empty($attributes)) {
            return true;
         }

         $keys = array();
         $values = array();

         foreach ($attributes as $key => $value) {
            $keys[] = "`$key` = ?";
            $values[] = $value;
         }

         $query = sprintf("UPDATE `%s` SET %s WHERE `id` = ?",
                          $this->table, implode(", ", $keys));
         $values[] = $id;
         $this->query($query, $values);

         return $id;
      }

      function delete($id) {
         $this->query(
            "DELETE FROM `{$this->table}` WHERE `id` = ?", $id
         );

         return $id;
      }

      function find($id, $value=null) {
         if ($value) {
            $key = $id;
         } else {
            $key = 'id';
            $value = $id;
         }

         return $this->query(
            "SELECT * FROM `{$this->table}` WHERE `$key` = ? LIMIT 1", $value
         )->fetch_load($this->model);
      }

      function find_all() {
         list($conditions, $values) = $this->build_conditions(func_get_args());
         return $this->query(
            "SELECT * FROM `{$this->table}`$conditions", (array) $values
         )->fetch_all_load($this->model);
      }

      function find_first() {
      }

      function __call($method, $args) {
         print "__call\n";
      }

      function count() {
         return intval($this->query(
            "SELECT count(*) FROM `{$this->table}`"
         )->fetch_column());
      }

      protected function build_select($options) {
      }

      protected function build_conditions($conditions) {
         if (!is_array($conditions)) {
            print "WTF???\n";
            $conditions = func_get_args();
         } elseif (count($conditions) == 1 and is_array($conditions[0])) {
            $conditions = $conditions[0];
         } elseif (empty($conditions)) {
            return null;
         }

         $condition = ' WHERE';
         $params = array();

         $keys = array_keys($conditions);
         $values = array_values($conditions);

         while (!empty($keys)) {
            $key = array_shift($keys);
            $value = array_shift($values);

            if (is_string($key)) {
               if (strstr($key, '?') !== false) {
                  $condition .= " $key";
               } else {
                  $condition .= " `$key` = ?";
               }
               $params[] = $value;
            } elseif ($count = substr_count($value, '?')) {
               $condition .= " $value";
               for ($i = 0; $i < $count; $i++) {
                  $params[] = array_shift_arg($values);
                  array_shift($keys);
               }
            } else {
               $condition .= " `$value` = ?";
               $params[] = array_shift_arg($values);
               array_shift($keys);
            }
         }

         return array($condition, $params);
      }
   }

?>
