<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class DatabaseAdapter extends Object
   {
      protected $connection;

      static function load($driver, $connection) {
         $driver = strtolower($driver);
         require_once LIB."database/adapters/{$driver}_adapter.php";
         $adapter = ucfirst($driver).'Adapter';
         return new $adapter($connection);
      }

      function __construct($connection) {
         $this->connection = $connection;
      }

      function get_tables() {
      }

      function get_table_attributes($table) {
      }
   }

?>
