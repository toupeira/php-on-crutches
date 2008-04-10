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
         $names = array_keys($config);

         if ($name == 'default' and !isset($config[$name])) {
            $real_name = array_shift($names);
         } else {
            $real_name = $name;
         }

         if (defined('TESTING')) {
            $real_name .= '_test';
         }

         if (is_array($options = $config[$real_name])) {
            if ($driver = array_delete($options, 'driver')) {
               $file = LIB."database/adapters/{$driver}_adapter.php";
               if (is_file($file)) {
                  require_once $file;
                  $adapter = ucfirst($driver).'Adapter';
               } else {
                  $adapter = get_class();
               }

               return self::$connections[$name] = new $adapter($real_name, $options);
            } else {
               throw new ConfigurationError("No driver set for database '$real_name'");
            }
         } else {
            throw new ConfigurationError("Unconfigured database '$real_name'");
         }
      }

      protected function __construct($name, $options) {
         $this->name = $name;

         list($user, $pass) = array_delete($options, 'username', 'password');
         $this->connection = new PDO($this->get_dsn($options), $user, $pass);
         $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }

      function get_name() {
         return $this->name;
      }

      function execute($sql, $params=null) {
         if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
         }

         log_debug("Database query: [{$this->name}] $sql");

         $stmt = $this->connection->prepare(
            $sql, array(PDO::ATTR_STATEMENT_CLASS => array(DatabaseStatement))
         );
         $stmt->setFetchMode(PDO::FETCH_ASSOC);
         return $stmt->execute($params);
      }

      function insert_id() {
         return $this->connection->lastInsertId();
      }

      function get_dsn($options) {
         throw new NotImplemented(get_class()." doesn't implement 'get_dsn'");
      }

      function get_timestamp() {
         throw new NotImplemented(get_class()." doesn't implement 'get_timestamp'");
      }

      function fetch_tables() {
         throw new NotImplemented(get_class()." doesn't implement 'fetch_tables'");
      }

      function fetch_attributes($table) {
         throw new NotImplemented(get_class()." doesn't implement 'fetch_attributes'");
      }

      function get_tables() {
         $key = "db-{$this->name}-tables";
         if ($tables = cache($key)) {
            return $tables;
         } else {
            return cache_set($key, $this->fetch_tables());
         }
      }

      function table_attributes($table) {
         $key = "db-{$this->name}-attributes-$table";
         if ($attributes = cache($key)) {
            return $attributes;
         } else {
            return cache_set($key, $this->fetch_attributes($table));
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
         if ($data = $this->fetch()) {
            $object = new $class();
            $object->load($data);
            return $object;
         }
      }

      function fetch_all_load($class) {
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
