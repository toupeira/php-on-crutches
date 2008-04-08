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
            $this->model = $model;
            $model = new $model();
         }

         if (! $model instanceof ActiveRecord) {
            throw new ApplicationError("Invalid model '".get_class($this)."'");
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

      function execute() {
         $args = func_get_args();
         return call_user_func_array(array($this->get_connection(), execute), $args);
      }

      function create($attributes) {
         $model = new $this->model($attributes);
         return $model->save();
      }

      function insert($attributes) {
         $columns = array();
         $keys = array();
         $values = array();

         foreach ($attributes as $key => $value) {
            $columns[] = "`$key`";
            $keys[] = '?';
            $values[] = $value;
         }

         if (in_array('created_at', $this->get_attributes()) and !isset($attributes['created_at'])) {
            $columns[] = "`created_at`";
            $keys[] = $this->get_connection()->get_timestamp();
         }

         $query = sprintf("INSERT INTO `%s` (%s) VALUES (%s)",
                          $this->table, implode(", ", $columns), implode(", ", $keys));
         $this->execute($query, $values);

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

         if (in_array('updated_at', $this->get_attributes()) and !isset($attributes['updated_at'])) {
            $keys[] = "`updated_at` = ".$this->get_connection()->get_timestamp();
         }

         $query = sprintf("UPDATE `%s` SET %s WHERE `id` = ?",
                          $this->table, implode(", ", $keys));
         $values[] = $id;
         $this->execute($query, $values);

         return $id;
      }

      function delete($id) {
         list($where, $values) = $this->build_where(func_get_args());
         if (empty($where)) {
            throw new ApplicationError("No conditions given");
         }

         $this->execute("DELETE FROM `{$this->table}`$where", (array) $values);
         return $id;
      }

      function delete_all() {
         $this->execute("DELETE FROM `{$this->table}`");
      }

      function find($id) {
         list($select, $values) = $this->build_select(func_get_args(),
            array('limit' => 1));
         return $this->execute($select, (array) $values)->fetch_load($this->model);
      }

      function find_all() {
         list($select, $values) = $this->build_select(func_get_args());
         return $this->execute($select, (array) $values)->fetch_all_load($this->model);
      }

      function find_by_sql($sql) {
         $params = array_slice(func_get_args(), 1);
         return $this->execute($sql, $params)->fetch_all_load($this->model);
      }

      # Handle find(_all)_by_* calls
      function __call($method, $args) {
         if (preg_match('/^(find(?:_all)?)_(by|like)_(\w+?)(?:_(and|or)_(\w+))?$/', $method, $match)) {
            list($method, $finder, $equality, $key, $operator, $keys) = $match;
            $argc = count($args);

            $equality = ($equality == 'by') ? '=' : 'LIKE';

            if ($operator) {
               $keys = explode("_{$operator}_", $keys);
               array_unshift($keys, $key);
               if ($argc < count($keys) or $argc > count($keys) + 1) {
                  $keys = implode("', '", $keys);
                  throw new ApplicationError("Wrong number of arguments for keys '$keys'");
               }

               $where = '';
               $op = '';
               foreach ($keys as $key) {
                  $where .= "$op`$key` $equality ?";
                  $params[] = array_shift($args);
                  $op = ' '.strtoupper($operator).' ';
               }

               array_unshift($params, $where);
               if (is_array($options = array_shift($args))) {
                  $params[] = $options;
               }

               return call_user_func_array(array($this, $finder), $params);
            } else {
               if ($argc < 1 or $argc > 2) {
                  throw new ApplicationError("Wrong number of arguments for key '$key'");
               }
               return $this->$finder("`$key` $equality ?", $args[0]);
            }
         } else {
            throw new ApplicationError("Invalid method '$method'");
         }
      }

      function count() {
         list($select, $values) = $this->build_select(func_get_args(), array('select' => 'count(*)'));
         return intval($this->execute($select, (array) $values)->fetch_column());
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
            # Conditions are passed directly as arguments
            $conditions = func_get_args();
         } elseif (count($conditions) == 1 and is_array($conditions[0])) {
            # Conditions are passed as array
            #$conditions = $conditions[0];
         } elseif (empty($conditions)) {
            return null;
         }

         $where = '';
         $operator = ' WHERE';
         $params = array();

         $keys = array_keys($conditions);
         $values = array_values($conditions);

         while (!empty($keys)) {
            $key = array_shift($keys);
            $value = array_shift($values);

            if ($where and $operator == ' WHERE') {
               $operator = ' AND';
            }

            if (is_string($key)) {
               $where .= $operator;

               if ($count = substr_count($key, '?')) {
                  # Use array key as WHERE clause
                  #   e.g.: find(array('key LIKE ?' => $value))
                  #
                  $where .= " $key";
               } else {
                  # Use array key as column name
                  #   e.g.: find(array('key' => $value))
                  #
                  $where .= " `$key` = ?";
                  $count = 1;
               }

               # Add array value as parameter for each placeholder
               for ($i = 0; $i < $count; $i++) {
                  $params[] = $value;
               }

            } elseif (is_numeric($value)) {
               # Use array value as ID
               #   e.g.: find($id)
               #
               $where .= "$operator `id` = ?";
               $params[] = $value;

               # Allow passing multiple IDs
               $operator = ' OR';

            } elseif ($count = substr_count($value, '?')) {
               # Use array value as WHERE clause
               #   e.g.: find('key LIKE ?', $value)
               #
               $where .= "$operator $value";
               for ($i = 0; $i < $count; $i++) {
                  $params[] = array_shift_arg($values);
                  array_shift($keys);
               }

            } elseif ($value == 'and' or $value == 'or') {
               # Change the operator
               $operator = ' '.strtoupper($value);

            } elseif (strstr($value, ' ') !== false) {
               # Use array value as literal WHERE clause,
               # without placeholders
               #   e.g.: find('key LIKE "%value%"')
               $where .= "$operator $value";
               array_shift($keys);

            } elseif (is_string($value) and !blank($value)) {
               # Use array value as column name
               #   e.g.: find('key', $value)
               $where .= "$operator `$value` = ?";
               $params[] = array_shift_arg($values);
               array_shift($keys);

            } else {
               throw new ApplicationError("Invalid argument '$value'");
            }
         }

         return array($where, $params);
      }
   }

?>
