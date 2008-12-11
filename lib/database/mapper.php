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

   abstract class DatabaseMapper extends ModelMapper
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

      protected $_table;
      protected $_primary_key = 'id';
      protected $_database = 'default';
      protected $_connection;

      protected $_order;
      protected $_page_size;

      protected $_associations = array();
      protected $_has_one = array();
      protected $_has_many = array();
      protected $_belongs_to = array();

      function __construct() {
         parent::__construct();

         if (empty($this->_database)) {
            throw new ConfigurationError("No database set for model '{$this->_model}'");
         } elseif (empty($this->_table) and !($this->_table = tableize($this->_model))) {
            throw new ConfigurationError("No table set for model '{$this->_model}'");
         } elseif (empty($this->_primary_key)) {
            throw new ConfigurationError("No primary key set for model '{$this->_model}'");
         }

         foreach (array('has_many', 'has_one', 'belongs_to') as $type) {
            if (!empty($this->{'_'.$type})) {
               require_once LIB."database/associations/{$type}_association.php";
               $association = camelize($type).'Association';
               foreach ($this->{'_'.$type} as $key => $options) {
                  if (is_numeric($key)) {
                     $key = $options;
                     $options = null;
                  }

                  $this->_associations[$key] = new $association($this->_model, classify($key), $options);
               }
            }
         }
      }

      function inspect() {
         return parent::inspect(array(
            'model'    => $this->_model,
            'database' => $this->_database,
            'table'    => $this->_table,
         ));
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

      function get_key_type() {
         return $this->attributes[$this->_primary_key]['type'];
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

      function has_many($key=null) {
         return $key ? in_array(tableize($key), $this->_has_many)
                     : $this->_has_many;
      }

      function has_one($key=null) {
         return $key ? in_array(underscore(singularize($key)), $this->_has_one)
                     : $this->_has_one;
      }

      function belongs_to($key=null) {
         return $key ? in_array(underscore(singularize($key)), $this->_belongs_to)
                     : $this->_belongs_to;
      }

      function execute() {
         $args = func_get_args();
         return call_user_func_array(array($this->connection, 'execute'), $args);
      }

      function get_query_set() {
         if (class_exists($class = $this->_model.'QuerySet')) {
            return new $class($this);
         } else {
            return new QuerySet($this);
         }
      }

      # Forward accesses to non-existent properties to a QuerySet
      function __get($key) {
         if (method_exists($this, $setter = "get_$key")) {
            return $this->$setter();
         } elseif (method_exists($this, $key)) {
            return $this->$key();
         } else {
            return $this->get_query_set()->$key;
         }
      }

      # Forward calls to non-existent methods to a QuerySet
      function __call($method, $args) {
         return call_user_func_array(array($this->get_query_set(), $method), $args);
      }

      function find($conditions) {
         $args = func_get_args();
         return $this->__call('find', $args);
      }

      function find_all($conditions=null) {
         $args = func_get_args();
         return $this->__call('where', $args);
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
               $values[] = $this->convert($value);
            }
         }

         if ($this->attributes['created_at'] and !isset($attributes['created_at'])) {
            $columns[] = "`created_at`";
            $keys[] = $this->connection->timestamp;
         }

         $query = sprintf("INSERT INTO `%s` (%s) VALUES (%s)",
                          $this->_table, implode(", ", $columns), implode(", ", $keys));

         if ($this->execute($query, $values)) {
            return array(
               $this->_primary_key => $this->connection->insert_id()
            );
         }
      }

      function update($conditions, $attributes, $force=false) {
         if (empty($attributes) and !$force) {
            return true;
         }

         $keys = array();
         $values = array();

         foreach ((array) $attributes as $key => $value) {
            if (is_numeric($key)) {
               $keys[] = $value;
            } elseif ($count = substr_count($key, '?')) {
               $keys[] = $key;
               for ($i = 0; $i < $count; $i++) {
                  $values[] = $this->convert($value);
               }
            } elseif ($value === '') {
               $keys[] = "`$key` = NULL";
            } else {
               $keys[] = "`$key` = ?";
               $values[] = $this->convert($value);
            }
         }

         if ($this->attributes['updated_at'] and !isset($attributes['updated_at'])) {
            $keys[] = "`updated_at` = ".$this->connection->timestamp;
         } elseif (empty($attributes)) {
            return true;
         }

         list($conditions, $condition_values) = $this->build_condition(
            is_object($conditions) ? $conditions->id : $conditions
         );

         if (blank($conditions)) {
            throw new ApplicationError("No conditions given");
         } else {
            $values = array_merge($values, $condition_values);
         }

         $query = sprintf("UPDATE `%s` SET %s WHERE $conditions",
                          $this->_table, implode(", ", $keys));

         return is_object($this->execute($query, $values));
      }

      function delete($conditions) {
         list($conditions, $values) = $this->build_condition(func_get_args());
         if (blank($conditions)) {
            throw new ApplicationError("No conditions given");
         }

         return is_object($this->execute("DELETE FROM `{$this->_table}` WHERE $conditions", (array) $values));
      }

      function delete_all($force=false) {
         if ($force) {
            $this->execute("DELETE FROM `{$this->_table}`");
         } else {
            throw new ApplicationError("Not unless you really want to");
         }
      }

      function build_condition($conditions) {
         if (!is_array($conditions)) {
            # Conditions are passed directly as arguments
            $conditions = func_get_args();
         } elseif (count($conditions) == 1 and is_array($conditions[0])) {
            # Conditions are passed as nested array
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
            $value = $this->convert(array_shift($values));

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

                  # Add array value as parameter for each placeholder
                  for ($i = 0; $i < $count; $i++) {
                     $params[] = $value;
                  }

               } else {
                  # Use array key as column name
                  #   e.g.: find(array('key' => $value))
                  #
                  $condition .= $this->add_condition($key, $value, $params);
               }

            } elseif (is_array($value)) {
               # Use array value as list of ID values
               #   e.g.: find(array(1,2,3))
               #
               $condition .= $operator.$this->add_condition(
                  "`{$this->_table}`.`{$this->_primary_key}`",
                  $value, $params
               );

            } elseif (((is_numeric($value) or is_numeric($value[0])) and
                        $this->key_type == 'integer') or
                      (is_string($value) and empty($values) and
                        $this->key_type == 'string'))
            {
               # Use array value as ID
               #   e.g.: find($id)
               #
               $condition .= "$operator`{$this->_table}`.`{$this->_primary_key}` = ?";
               $params[] = $value;

               # Allow passing multiple IDs
               $operator = ' OR ';

            } elseif ($value == 'and' or $value == 'or') {
               # Change the operator
               $operator = ' '.strtoupper($value).' ';

            } elseif ($count = substr_count($value, '?')) {
               # Use array value as condition with placeholders
               #   e.g.: find('key LIKE ?', $value)
               #
               $condition .= "$operator$value";
               for ($i = 0; $i < $count; $i++) {
                  $params[] = $this->convert(array_shift_arg($values));
                  array_shift($keys);
               }

            } elseif (strstr($value, ' ') !== false) {
               # Use array value as literal condition, without placeholders
               #   e.g.: find('key LIKE "%value%"')
               $condition .= "$operator$value";

            } elseif (is_string($value) and !blank($value)) {
               # Use array value as column name
               #   e.g.: find('key', $value)
               $key = $value;
               $value = $this->convert(array_shift_arg($values));
               array_shift($keys);

               $condition .= $operator.$this->add_condition($key, $value, $params);

            } elseif (!is_null($value)) {
               throw new TypeError($value);
            }
         }

         return array($condition, $params);
      }

      protected function convert($value) {
         if (is_object($value)) {
            if (!$value instanceof ActiveRecord) {
               throw new TypeError($value, "Invalid class '%s'");
            } elseif ($value->changed) {
               throw new ApplicationError("Can't use unsaved model as parameter");
            } else {
               return $value->id;
            }
         } else {
            return $value;
         }
      }

      protected function add_condition($key, $value, &$params) {
         if (is_null($value) or (is_array($value) and !$value)) {
            $condition = "$key IS NULL";
         } elseif (is_array($value)) {
            $values = array();
            foreach ($value as $value) {
               $values[] = '?';
               $params[] = $value;
            }

            $condition = "$key IN (".implode(', ', $values).")";
         } else {
            $condition = "$key = ?";
            $params[] = $value;
         }

         return $condition;
      }
   }

?>
