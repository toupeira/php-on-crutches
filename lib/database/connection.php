<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class DatabaseConnection extends Object
   {
      static private $connections;

      private $name;
      private $connection;

      static function load($name) {
         if ($connection = self::$connections[$name]) {
            return $connection;
         }

         $config = $GLOBALS['_DATABASE'];
         if ($name == 'default' and !isset($config[$name])) {
            $options = array_shift($config);
         } else {
            $options = $config[$name];
         }

         while (is_string($options)) {
            $options = $config[$options];
         }

         if (is_array($options)) {
            if (!isset($options['adapter'])) {
               throw new ConfigurationError("No adapter set for database '$name'");
            }

            $file = LIB."database/adapters/{$options['adapter']}_adapter.php";
            if (is_file($file)) {
               require_once $file;
               $adapter = ucfirst($options['adapter']).'Adapter';
            } else {
               $adapter = get_class();
            }

            return self::$connections[$name] = new $adapter($name, $options);
         } else {
            throw new ConfigurationError("Unconfigured database '$name'");
         }
      }

      protected function __construct($name, $options) {
         $this->name = $name;

         $this->connection = new PDO(
            $this->get_dsn($options), $options['username'], $options['password']
         );
         $this->connection->setAttribute(
            PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
         );
      }

      function get_name() {
         return $this->name;
      }

      function query($sql, $params=null) {
         if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
         }

         if (config('log_level') >= LOG_DEBUG) {
            $args = array_map(proc('var_export($a, true)'), $params);
            array_unshift($args, str_replace('?', '%s', $sql));
            log_debug("Database query: [{$this->name}] '".call_user_func_array(sprintf, $args)."'");
         }

         $stmt = $this->connection->prepare(
            $sql, array(PDO::ATTR_STATEMENT_CLASS => array(DatabaseStatement))
         );
         $stmt->setFetchMode(PDO::FETCH_ASSOC);
         return $stmt->execute($params);
      }

      function insert_id() {
         return $this->connection->lastInsertId();
      }

      function fetch_tables() {
         throw new ApplicationError(get_class()." doesn't implement 'fetch_tables'");
      }

      function fetch_attributes($table) {
         throw new ApplicationError(get_class()." doesn't implement 'fetch_attributes'");
      }

      function get_tables() {
         $key = "db-{$this->name}-tables";
         if ($tables = cache($key)) {
            return $tables;
         } else {
            return cache($key, $this->fetch_tables());
         }
      }

      function table_attributes($table) {
         $key = "db-{$this->name}-attributes-$table";
         if ($attributes = cache($key)) {
            return $attributes;
         } else {
            return cache($key, $this->fetch_attributes($table));
         }
      }
   }

   class DatabaseStatement extends PDOStatement
   {
      function execute($params=array()) {
         parent::execute($params);
         return $this;
      }

      function fetch_all() {
         return parent::fetchAll();
      }

      function fetch_column($column=0) {
         if (is_numeric($column)) {
            return parent::fetchColumn($column);
         } else {
            $record = $this->fetch();
            return $record[$column];
         }
      }

      function fetch_list($column=0) {
         $list = array();
         while (($value = $this->fetch_column($column))) {
            $list[] = $value;
         }

         return $list;
      }

      function fetch_load($class) {
         $class = get_class($class);
         if ($data = $this->fetch()) {
            $object = new $class();
            $object->load($data);
            return $object;
         }
      }

      function fetch_all_load($class) {
         $class = get_class($class);
         $objects = array();
         while ($data = $this->fetch()) {
            $object = new $class();
            $object->load($data);
            $objects[] = $object;
         }
         return $objects;
      }
   }

   class DatabaseTransaction {
      private $connection;
      private $finished;

      function __construct($connection) {
         $this->connection = $connection;
         $this->connection->beginTransaction();
      }

      function __destruct() {
         if (!$this->finished) {
            $this->connection->rollback();
         }
      }

      function commit() {
         $this->connection->commit();
         $this->finished = true;
      }

      function rollback() {
         $this->connection->rollback();
         $this->finished = true;
      }
   }

?>
