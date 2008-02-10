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
      private $driver;
      private $adapter;
      private $table_attributes;

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
            return self::$connections[$name] = new DatabaseConnection($name, $options);
         } else {
            raise("Unconfigured database '$name'");
         }
      }

      function __construct($name, $options) {
         $this->name = $name;

         $this->connection = new PDO(
            $options['dsn'], $options['username'], $options['password']
         );
         $this->connection->setAttribute(
            PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION
         );

         $this->driver = substr($options['dsn'], 0, strpos($options['dsn'], ':'));
         $this->adapter = DatabaseAdapter::load($this->driver, $this);
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

      # Find available tables
      function get_tables() {
         return $this->adapter->get_tables();
      }

      # Load model attributes from database schema
      function get_table_attributes($table) {
         if ($attributes = $this->table_attributes[$table]) {
            return $attributes;
         } else {
            return $this->table_attributes[$table] = $this->adapter->get_table_attributes($table);
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
         return parent::fetchColumn($column);
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

?>
