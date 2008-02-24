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
         return $this->get_connection()->table_attributes($this->table);
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

      function find($id) {
         if (is_null($id)) {
            raise("No ID given");
         }

         list($select, $values) = $this->build_select(func_get_args(), array('limit' => 1));
         return $this->query($select, (array) $values)->fetch_load($this->model);
      }

      function find_all() {
         list($select, $values) = $this->build_select(func_get_args());
         return $this->query($select, (array) $values)->fetch_all_load($this->model);
      }

      function find_by_sql($sql) {
         $params = array_slice(func_get_args(), 1);
         return $this->query($sql, $params)->fetch_all_load($this->model);
      }

      # Implement find(_all)_by_* calls
      function __call($method, $args) {
         if (preg_match('/^(find(?:_all)?)_by_(\w+?)(?:_(and|or)_(\w+))?$/', $method, $match)) {
            list($method, $finder, $key, $operator, $keys) = $match;

            if ($operator) {
               $keys = explode("_{$operator}_", $keys);
               array_unshift($keys, $key);
               if (count($args) < count($keys) or count($args) > count($keys) + 1) {
                  $keys = implode("', '", $keys);
                  raise("Wrong number of arguments for keys '$keys'");
               }

               $where = '';
               $op = '';
               foreach ($keys as $key) {
                  $where .= "$op`$key` = ?";
                  $params[] = array_shift($args);
                  $op = ' '.strtoupper($operator).' ';
               }

               array_unshift($params, $where);
               if (is_array($options = array_shift($args))) {
                  $params[] = $options;
               }

               return call_user_func_array(array($this, $finder), $params);
            } else {
               if ($args < 1 or $args > 2) {
                  raise("Wrong number of arguments for key '$key'");
               }
               return $this->$finder($key, $args[0]);
            }
         }
      }

      function count() {
         return intval($this->query(
            "SELECT count(*) FROM `{$this->table}`"
         )->fetch_column());
      }

      protected function build_select($args, $defaults=array()) {
         $options = array();
         $where_options = array();
         foreach ($args as $i => $arg) {
            if (is_array($arg) and $i == count($args) - 1) {
               $options = $arg;
            } else {
               $where_options[] = $arg;
            }
         }

         $options = array_merge(
            array('select' => '*'), $defaults, $options
         );

         if ($where_options) {
            $options['conditions'] = $where_options;
         }

         $params = array();
         $select = "SELECT {$options['select']} FROM `{$this->table}` {$options['joins']}";

         if ($conditions = $options['conditions']) {
            list($where, $params) = $this->build_where($conditions);
            $select .= $where;
         }

         if ($order = $options['order']) { $select .= " ORDER BY $order"; }
         if ($group = $options['group']) { $select .= " GROUP BY $group"; }
         if ($limit = $options['limit']) { $select .= " LIMIT $limit"; }
         if ($offset = $options['offset']) { $select .= " OFFSET $offset"; }

         return array($select, $params);
      }

      protected function build_where($conditions) {
         if (!is_array($conditions)) {
            $conditions = func_get_args();
         } elseif (count($conditions) == 1 and is_array($conditions[0])) {
            $conditions = $conditions[0];
         } elseif (empty($conditions)) {
            return null;
         }

         $where = ' WHERE';
         $operator = '';
         $params = array();

         $keys = array_keys($conditions);
         $values = array_values($conditions);

         while (!empty($keys)) {
            $key = array_shift($keys);
            $value = array_shift($values);

            $where .= $operator;

            if (is_string($key)) {
               if ($count = substr_count($key, '?')) {
                  $where .= " $key";
               } else {
                  $where .= " `$key` = ?";
               }

               for ($i = 0; $i < $count; $i++) {
                  $params[] = $value;
               }
            } elseif ($count = substr_count($value, '?')) {
               $where .= " $value";
               for ($i = 0; $i < $count; $i++) {
                  $params[] = array_shift_arg($values);
                  array_shift($keys);
               }
            } elseif (is_numeric($value)) {
               $where .= " `id` = ?";
               $params[] = $value;
            } elseif (is_string($value)) {
               $where .= " `$value` = ?";
               $params[] = array_shift_arg($values);
               array_shift($keys);
            }

            $operator = ' AND';
         }

         return array($where, $params);
      }
   }

?>
