<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require LIB.'database/query_set.php';
   require LIB.'database/association.php';

   abstract class DatabaseMapper extends Object
   {
      static function load($model) {
         static $_cache;

         is_object($model) and $model = get_class($model);

         if ($mapper = $_cache[$model]) {
            return $mapper;
         } else {
            $mapper = $model.'Mapper';
            return $_cache[$model] = new $mapper();
         }
      }

      protected $_model;
      protected $_table;
      protected $_primary_key = 'id';
      protected $_database = 'default';
      protected $_connection;

      protected $_attributes;
      protected $_defaults;
      protected $_order;
      protected $_page_size;

      protected $_associations;
      protected $_has_one;
      protected $_has_many;
      protected $_belongs_to;

      function __construct() {
         $this->_model = substr(get_class($this), 0, -6);

         if (empty($this->_database)) {
            throw new ConfigurationError("No database set for model '{$this->_model}'");
         } elseif (empty($this->_table)) {
            throw new ConfigurationError("No table set for model '{$this->_model}'");
         }

         foreach (array('has_one', 'has_many', 'has_many_through', 'belongs_to') as $type) {
            if (!empty($this->{'_'.$type})) {
               require_once LIB."database/associations/{$type}_association.php";
               $association = camelize($type).'Association';
               foreach ($this->{'_'.$type} as $key => $related) {
                  $this->_associations[$key] = new $association($this->_model, $related);
               }
            }
         }
      }

      function __toString() {
         return parent::__toString(array(
            'model'    => $this->_model,
            'database' => $this->_database,
            'table'    => $this->_table,
         ));
      }

      function get_model() {
         return $this->_model;
      }

      function get_database() {
         return $this->_database;
      }

      function get_table() {
         return $this->_table;
      }

      function get_primary_key() {
         return $this->_primary_key;
      }

      function get_connection() {
         if (!$this->_connection) {
            $this->_connection = DatabaseConnection::load($this->_database);
         }

         return $this->_connection;
      }

      function get_attributes() {
         if ($this->_attributes) {
            return $this->_attributes;
         } else {
            return $this->_attributes = $this->connection->table_attributes($this->_table);
         }
      }

      function get_defaults() {
         return $this->_defaults;
      }

      function get_order() {
         return $this->_order;
      }

      function set_order($order) {
         return $this->_order = $order;
      }

      function get_page_size() {
         return $this->_page_size;
      }

      function set_page_size($page_size) {
         return $this->_page_size = $page_size;
      }

      function get_associations() {
         return $this->_associations;
      }

      function execute() {
         $args = func_get_args();
         return call_user_func_array(array($this->connection, execute), $args);
      }

      function create(array $attributes) {
         $model = new $this->_model($attributes);
         return $model->save();
      }

      function insert(array $attributes) {
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

         if ($this->attributes['created_at'] and !isset($attributes['created_at'])) {
            $columns[] = "`created_at`";
            $keys[] = $this->connection->timestamp;
         }

         $query = sprintf("INSERT INTO `%s` (%s) VALUES (%s)",
                          $this->_table, implode(", ", $columns), implode(", ", $keys));

         if ($this->execute($query, $values)) {
            return $this->connection->insert_id();
         }
      }

      function update(array $attributes, $conditions, $force=false) {
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

         if ($this->attributes['updated_at'] and !isset($attributes['updated_at'])) {
            $keys[] = "`updated_at` = ".$this->connection->timestamp;
         } elseif (empty($attributes)) {
            return true;
         }

         list($conditions, $condition_values) = $this->build_condition($conditions);
         if (blank($conditions)) {
            throw new ApplicationError("No conditions given");
         } else {
            $values = array_merge($values, $condition_values);
         }

         $query = sprintf("UPDATE `%s` SET %s WHERE $conditions",
                          $this->_table, implode(", ", $keys));

         return is_object($this->execute($query, $values));
      }

      function delete($id) {
         list($conditions, $values) = $this->build_condition(func_get_args());
         if (blank($conditions)) {
            throw new ApplicationError("No conditions given");
         }

         return is_object($this->execute("DELETE FROM `{$this->_table}` WHERE $conditions", (array) $values));
      }

      function destroy($conditions) {
         $args = func_get_args();
         $objects = call_user_func_array(
            array($this->get_query_set(), 'where'), $args
         );

         $status = false;
         foreach ($objects as $object) {
            $status = $object->destroy();
         }

         return $status;
      }

      function delete_all() {
         $this->execute("DELETE FROM `{$this->_table}`");
      }

      function get_query_set() {
         if (class_exists($class = $this->_model.'QuerySet')) {
            return new $class($this);
         } else {
            return new QuerySet($this);
         }
      }

      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } else {
            return $this->get_query_set()->$getter();
         }
      }

      function __set($key, $value) {
         $setter = "set_$key";
         if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
         } else {
            return $this->get_query_set()->$setter($key);
         }
      }

      function __call($method, $args) {
         return call_user_func_array(array($this->get_query_set(), $method), $args);
      }

      function build_condition($conditions) {
         if (!is_array($conditions)) {
            # Conditions are passed directly as arguments
            $conditions = func_get_args();
         } elseif (count($conditions) == 1 and is_array($conditions[0])) {
            # Conditions are passed as array
            $conditions = $conditions[0];
         }

         if (empty($conditions)) {
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
                  $condition .= $key;
               } else {
                  # Use array key as column name
                  #   e.g.: find(array('key' => $value))
                  #
                  $condition .= "$key = ?";
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
               $condition .= "$operator`{$this->_table}`.`{$this->_primary_key}` = ?";
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
               $condition .= "$operator$value = ?";
               $params[] = array_shift_arg($values);
               array_shift($keys);

            } elseif (!is_null($value)) {
               throw new TypeError($value);
            }
         }

         return array($condition, $params);
      }
   }

?>
