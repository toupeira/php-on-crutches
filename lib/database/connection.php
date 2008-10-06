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
      static function load($name) {
         static $_cache;

         if ($connection = $_cache[$name]) {
            return $connection;
         }

         $config = config('database');
         $names = array_keys($config);

         if ($name == 'default' and !isset($config[$name])) {
            $real_name = array_shift($names);
         } else {
            $real_name = $name;
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
               return $_cache[$name] = $connection;
            } else {
               throw new ConfigurationError("No driver set for database '$real_name'");
            }
         } else {
            throw new ConfigurationError("Unconfigured database '$real_name'");
         }
      }

      protected $_name;
      protected $_options;
      protected $_connection;

      protected function __construct($name, array $options) {
         $this->_name = $name;
         $this->_options = $options;

         list($user, $pass) = array_delete($options, 'username', 'password');
         $this->_connection = new PDO(
            $this->get_dsn($options), $user, $pass, array(
               PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
               PDO::ATTR_PERSISTENT       => true,
            ) + (array) $this->get_attributes()
         );
      }

      function __toString() {
         return parent::__toString($this->_options);
      }

      function get_name() {
         return $this->_name;
      }

      function get_options() {
         return $this->_options;
      }

      function execute($sql, array $params=null) {
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

            $query = call_user_func_array(sprintf, $args);
            log_info("  SQL [{$this->name}] [1m$query[0m");
            Dispatcher::$db_queries++;
            Dispatcher::$db_queries_sql[$this->name][] = $query;

            if (config('analyze_queries') and substr($sql, 0, 6) == 'SELECT') {
               $this->analyze_query($sql, $params);
            }
         }

         $stmt = $this->_connection->prepare(
            $sql, array(PDO::ATTR_STATEMENT_CLASS => array(DatabaseStatement))
         );
         $stmt->setFetchMode(PDO::FETCH_ASSOC);
         return $stmt->execute($params);
      }

      function insert_id() {
         return $this->_connection->lastInsertId();
      }

      function get_tables() {
         $key = "db-{$this->_name}-tables";
         if ($tables = cache($key)) {
            return $tables;
         } else {
            return cache_set($key, $this->fetch_tables());
         }
      }

      function table_attributes($table) {
         $key = "db-{$this->_name}-attributes-$table";
         if ($attributes = cache($key)) {
            return $attributes;
         } else {
            return cache_set($key, $this->fetch_attributes($table));
         }
      }

      function parse_type($type) {
         if (preg_match('/^(\w+)\(([0-9]+)\)/', $type, $match)) {
            $type = $match[1];
            $size = $match[2];
         } else {
            $size = -1;
         }

         switch (strtolower($type)) {
            case 'char':
            case 'varchar':
               $type = 'string';
               break;
            case 'int':
            case 'tinyint':
            case 'bigint':
               $type = 'integer';
               break;
            case 'double':
               $type = 'float';
               break;
            case 'datetime':
            case 'timestamp':
               $type = 'datetime';
               break;
         }

         return array($type, $size);
      }

      function get_dsn() {
         throw new NotImplemented(get_class()." doesn't implement 'get_dsn'");
      }

      function get_attributes() {
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

      function analyze_query($sql, array $params) {
         throw new NotImplemented(get_class()." doesn't implement 'analyze_query'");
      }
   }

   class DatabaseStatement extends PDOStatement
   {
      function execute(array $params=null) {
         parent::execute((array) $params);
         return $this;
      }

      function row_count() {
         return parent::rowCount();
      }

      function column_count() {
         return parent::columnCount();
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
      protected $connection;
      protected $finished;

      function __construct(PDO $connection) {
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
