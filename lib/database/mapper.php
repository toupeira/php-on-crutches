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
            if ($value === '') {
               $keys[] = 'NULL';
            } else {
               $keys[] = '?';
               $values[] = $value;
            }
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

      function update($id, $attributes, $force=false) {
         if (empty($attributes) and !$force) {
            return true;
         }

         $keys = array();
         $values = array();

         foreach ($attributes as $key => $value) {
            if ($value === '') {
               $keys[] = "`$key` = NULL";
            } else {
               $keys[] = "`$key` = ?";
               $values[] = $value;
            }
         }

         if (in_array('updated_at', $this->get_attributes()) and !isset($attributes['updated_at'])) {
            $keys[] = "`updated_at` = ".$this->get_connection()->get_timestamp();
         } elseif (empty($attributes)) {
            return true;
         }

         $query = sprintf("UPDATE `%s` SET %s WHERE `id` = ?",
                          $this->table, implode(", ", $keys));
         $values[] = $id;
         $this->execute($query, $values);

         return $id;
      }

      function delete($id) {
         list($conditions, $values) = $this->build_condition(func_get_args());
         if (blank($conditions)) {
            throw new ApplicationError("No conditions given");
         }

         $this->execute("DELETE FROM `{$this->table}` WHERE $conditions", (array) $values);
         return $id;
      }

      function delete_all() {
         $this->execute("DELETE FROM `{$this->table}`");
      }

      function find($id) {
         if ($id) {
            list($select, $values) = $this->build_select(func_get_args(),
               array('limit' => 1));
            return $this->execute($select, (array) $values)->fetch_load($this->model);
         }
      }

      function find_all() {
         list($select, $values) = $this->build_select(func_get_args());
         return $this->execute($select, (array) $values)->fetch_all_load($this->model);
      }

      function find_pairs($key='id', $value='name', $blank=true) {
         if ($blank === true) {
            $pairs = array(null => '');
         } elseif ($blank !== false) {
            $pairs = array(null => $blank);
         } else {
            $pairs = array();
         }

         foreach ($this->find_all() as $model) {
            $pairs[$model->$key] = $model->$value;
         }

         return $pairs;
      }

      function find_by_sql($sql) {
         $values = array_slice(func_get_args(), 1);
         return $this->execute($sql, $values)->fetch_all_load($this->model);
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
            } else {
               $keys = (array) $key;
            }

            $where = '';
            $op = '';
            foreach ($keys as $key) {
               $where .= "$op`$key` $equality ?";
               $params[] = array_shift_arg($args);
               $op = ' '.strtoupper($operator).' ';
            }

            array_unshift($params, $where);

            if ($args) {
               if (is_array($options = array_shift($args))) {
                  $params[] = $options;
               } else {
                  throw new ApplicationError('Too many arguments');
               }
            }

            return call_user_func_array(array($this, $finder), $params);

         } else {
            throw new ApplicationError("Invalid method '$method'");
         }
      }

      function count() {
         list($select, $values) = $this->build_select(func_get_args(), array('select' => 'count(*)'));
         return intval($this->execute($select, (array) $values)->fetch_column());
      }

      protected function build_select($args, $defaults=null) {
         $options = array();
         $conditions = array();
         foreach ($args as $i => $arg) {
            if (is_array($arg) and $i == count($args) - 1) {
               $options = $arg;
            } else {
               $conditions[] = $arg;
            }
         }

         $options = array_merge(
            array('select' => '*', 'from' => "`{$this->table}`"),
            (array) $defaults, $options
         );

         if ($conditions) {
            $options['conditions'] = $conditions;
         }

         $params = array();
         $select = 'SELECT '
                 . implode(', ', (array) $options['select'])
                 . ' FROM '
                 . implode(', ', (array) $options['from']);

         if ($joins = $options['joins']) {
            $select .= ' '.implode(' ', (array) $joins);
         }

         if ($conditions = $options['conditions']) {
            list($conditions, $params) = $this->build_condition($conditions);
            if (!blank($conditions)) {
               $select .= " WHERE $conditions";
            }
         }

         if ($group = $options['group']) { $select .= " GROUP BY $group"; }

         if ($conditions = $options['having']) {
            list($conditions, $having_params) = $this->build_condition($conditions);
            $select .= " HAVING $conditions";
            $params = array_merge($params, $having_params);
         }

         if ($order = $options['order']) {
            $select .= ' ORDER BY '.implode(', ', (array) $order);
         }

         if ($limit = $options['limit']) { $select .= " LIMIT $limit"; }
         if ($offset = $options['offset']) { $select .= " OFFSET $offset"; }

         return array($select, $params);
      }

      protected function build_condition($conditions) {
         if (!is_array($conditions)) {
            # Conditions are passed directly as arguments
            $conditions = func_get_args();
         } elseif (count($conditions) == 1 and is_array($conditions[0])) {
            # Conditions are passed as array
            #$conditions = $conditions[0];
         } elseif (empty($conditions)) {
            return null;
         }

         $condition = '';
         $operator = '';
         $params = array();

         $keys = array_keys($conditions);
         $values = array_values($conditions);

         while (!empty($keys)) {
            $key = array_shift($keys);
            $value = array_shift($values);

            if ($condition and $operator == '') {
               $operator = ' AND ';
            }

            if (is_string($key)) {
               $condition .= $operator;

               if ($count = substr_count($key, '?')) {
                  # Use array key as condition with placeholders
                  #   e.g.: find(array('key LIKE ?' => $value))
                  #
                  $condition .= " $key";
               } else {
                  # Use array key as column name
                  #   e.g.: find(array('key' => $value))
                  #
                  $condition .= " `$key` = ?";
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
               $condition .= "$operator`id` = ?";
               $params[] = $value;

               # Allow passing multiple IDs
               $operator = ' OR ';

            } elseif ($count = substr_count($value, '?')) {
               # Use array value as condition with placeholders
               #   e.g.: find('key LIKE ?', $value)
               #
               $condition .= "$operator$value";
               for ($i = 0; $i < $count; $i++) {
                  $params[] = array_shift_arg($values);
                  array_shift($keys);
               }

            } elseif ($value == 'and' or $value == 'or') {
               # Change the operator
               $operator = ' '.strtoupper($value).' ';

            } elseif (strstr($value, ' ') !== false) {
               # Use array value as literal condition, without placeholders
               #   e.g.: find('key LIKE "%value%"')
               $condition .= "$operator$value";

            } elseif (is_string($value) and !blank($value)) {
               # Use array value as column name
               #   e.g.: find('key', $value)
               $condition .= "$operator`$value` = ?";
               $params[] = array_shift_arg($values);
               array_shift($keys);

            } elseif (!is_null($value)) {
               throw new ApplicationError("Invalid argument '$value'");
            }
         }

         return array($condition, $params);
      }
   }

?>
