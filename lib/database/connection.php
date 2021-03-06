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
      static protected $_cache;

      static function load($name) {
         if ($connection = self::$_cache[$name]) {
            return $connection;
         }

         $config = config('database');
         $default = array_shift(array_keys($config));

         if ($name == 'default' and !isset($config[$name])) {
            $real_name = $default;
         } else {
            $real_name = $name;
            if ($name == $default) {
               $name = 'default';
            }
         }

         while (is_string($options = $config[$real_name])) {
            $real_name = $options;
         }

         if (is_array($options)) {
            $options = array_merge($options, (array) $options[ENVIRONMENT]);

            if ($driver = array_delete($options, 'driver')) {
               $file = LIB."database/adapters/{$driver}_adapter.php";
               if (is_file($file)) {
                  require_once $file;
                  $adapter = ucfirst($driver).'Adapter';
               } else {
                  $adapter = get_class();
               }

               $connection = new $adapter($real_name, $options);
               return self::$_cache[$name] = self::$_cache[$real_name] = $connection;
            } else {
               throw new ConfigurationError("No driver set for database '$real_name'");
            }
         } else {
            throw new ConfigurationError("Unconfigured database '$real_name'");
         }
      }

      static function close_all() {
         foreach ((array) self::$_cache as $connection) {
            $connection->close();
         }

         return true;
      }

      protected $_name;
      protected $_options;
      protected $_connection;
      protected $_transaction;

      protected function __construct($name, array $options) {
         $this->_name = $name;
         $this->_options = $options;
      }

      function inspect() {
         return parent::inspect($this->_options);
      }

      function get_name() {
         return $this->_name;
      }

      function get_display_name() {
         return $this->_name;
      }

      function get_options() {
         return $this->_options;
      }

      function get_connection() {
         if (!$this->_connection) {
            $this->open();
         }

         return $this->_connection;
      }

      function open() {
         if ($this->_connection) {
            return false;
         } else {
            log_debug("Opening database '{$this->_name}'");

            $attributes = array(
               PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_PERSISTENT => $this->_options['persistent'],
            ) + (array) array_delete($this->_options, 'attributes')
              + (array) $this->get_attributes();

            $this->_connection = new PDO(
               $this->get_dsn($this->_options),
               $this->_options['username'],
               $this->_options['password'],
               $attributes
            );

            return true;
         }
      }

      function close() {
         if ($this->_connection) {
            log_debug("Closing database '{$this->_name}'");
            $this->_connection = null;
            return true;
         } else {
            return false;
         }
      }

      function execute($sql, $params=null) {
         if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
         }

         if (log_level(LOG_INFO)) {
            $args = array();
            foreach ($params as $param) {
               $args[] = var_export($param, true);
            }
            array_unshift($args, str_replace('?', '%s',
               str_replace('%', '%%', $sql)
            ));

            if (!$query = @call_user_func_array('sprintf', $args)) {
               $query = $sql;
            }
            log_info("  SQL [{$this->name}] [1m$query[0m");
            Dispatcher::$db_queries++;

            if (config('debug_toolbar')) {
               Dispatcher::$db_queries_sql[$this->name][] = $query;
            }

            if (config('debug_queries') and substr($sql, 0, 6) == 'SELECT') {
               $this->analyze_query($sql, $params);
            }
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

      function get_tables() {
         $key = "db-{$this->_name}-tables";
         if ($tables = cache($key)) {
            return (array) $tables;
         } else {
            return cache_set($key, (array) $this->fetch_tables());
         }
      }

      function __get($key) {
         if ($this->connection and $table = $this->table($key) and !method_exists($this, "get_$key")) {
            return $table;
         } else {
            return parent::__get($key);
         }
      }

      function table($table) {
         if (in_array($table, (array) $this->tables)) {
            return new DatabaseMapper($this, $table);
         }
      }

      function table_attributes($table) {
         $key = "db-{$this->_name}-attributes-$table";
         if ($attributes = cache($key)) {
            return $attributes;
         } elseif (in_array($table, $this->tables)) {
            return cache_set($key, $this->fetch_columns($table));
         } else {
            throw new ConfigurationError("Table '{$this->options['database']}.$table' doesn't exist");
         }
      }

      function parse_type($type) {
         if (preg_match('/^(\w+)\(([0-9]+)\)/', $type, $match)) {
            $type = $match[1];
            $size = $match[2];
         } else {
            $size = -1;
         }

         $type = $full_type = strtolower($type);

         if (substr($type, -3) == 'int') {
            $type = ($size > 1 ? 'integer' : 'bool');
         } elseif (substr($type, -5) == 'float' or $type == 'double') {
            $type = 'float';
         } elseif (substr($type, -4) == 'char') {
            $type = 'string';
         } elseif (substr($type, -4) == 'text') {
            $type = 'text';
         } elseif (substr($type, -4) == 'blob') {
            $type = 'blob';
         } elseif ($type == 'datetime' or $type == 'timestamp') {
            $type = 'time';
         }

         return array($type, $full_type, $size);
      }

      function get_dsn() {
         throw new NotImplemented(get_class()." doesn't implement 'get_dsn'");
      }

      function get_attributes() {
      }

      function begin() {
         if ($this->_transaction) {
            new ApplicationError(sprintf("Transaction already active in database '%s'", $this->name));
         }

         return $this->_transaction = new DatabaseTransaction($this->connection);
      }

      function commit() {
         if (!$this->_transaction) {
            new ApplicationError(sprintf("No active transaction in database '%s'", $this->name));
         }

         if ($this->_transaction->commit()) {
            $this->_transaction = null;
            return true;
         } else {
            return false;
         }
      }

      function rollback() {
         if (!$this->_transaction) {
            new ApplicationError(sprintf("No active transaction in database '%s'", $this->name));
         }

         if ($this->_transaction->rollback()) {
            $this->_transaction = null;
            return true;
         } else {
            return false;
         }
      }

      function get_timestamp() {
         throw new NotImplemented(get_class()." doesn't implement 'get_timestamp'");
      }

      function fetch_tables() {
         throw new NotImplemented(get_class()." doesn't implement 'fetch_tables'");
      }

      function fetch_columns($table) {
         throw new NotImplemented(get_class()." doesn't implement 'fetch_columns'");
      }

      function analyze_query($sql, array $params) {
         throw new NotImplemented(get_class()." doesn't implement 'analyze_query'");
      }
   }

   class DatabaseStatement extends PDOStatement
   {
      protected $_fetch = false;

      function __get($key) {
         if (method_exists($this, $key)) {
            return $this->$key();
         } else {
            throw new UndefinedMethod($this, $key);
         }
      }

      function execute(array $params=null) {
         parent::execute((array) $params);
         $this->_fetch = (parent::columnCount() > 0);
         return $this;
      }

      function row_count() {
         return parent::rowCount();
      }

      function column_count() {
         return parent::columnCount();
      }

      function fetch_all($fetch_style=null) {
         if ($this->_fetch) {
            return parent::fetchAll($fetch_style);
         } else {
            return array();
         }
      }

      function fetch_column($column=0) {
         if (!$this->_fetch) {
            return;
         } elseif (is_numeric($column)) {
            return parent::fetchColumn($column);
         } else {
            $record = $this->fetch();
            return $record[$column];
         }
      }

      function fetch_list($column=0) {
         $list = array();

         if ($this->_fetch) {
            while (($value = $this->fetch_column($column))) {
               $list[] = $value;
            }
         }

         return $list;
      }

      function fetch_load($class) {
         if ($this->_fetch and $data = $this->fetch()) {
            $object = new $class();
            $object->load($data);
            return $object;
         }
      }

      function fetch_all_load($class) {
         if ($this->_fetch) {
            $objects = array();
            while ($data = $this->fetch()) {
               $object = new $class();
               $object->load($data);
               $objects[] = $object;
            }

            return $objects;
         } else {
            return array();
         }
      }
   }

   class DatabaseTransaction {
      protected $_connection;
      protected $_finished;

      function __construct(PDO $connection) {
         $this->_connection = $connection;
         $this->_connection->beginTransaction();
      }

      function __destruct() {
         if (!$this->_finished) {
            $this->_connection->rollback();
         }
      }

      function commit() {
         $this->_connection->commit();
         $this->_finished = true;
      }

      function rollback() {
         $this->_connection->rollback();
         $this->_finished = true;
      }
   }

?>
