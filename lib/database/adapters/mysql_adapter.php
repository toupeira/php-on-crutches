<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class MysqlAdapter extends DatabaseConnection
   {
      function get_dsn($options) {
         return "mysql:host={$options['hostname']};dbname={$options['database']}";
      }

      function get_timestamp() {
         return "now()";
      }

      function fetch_tables() {
         $tables = array();
         $rows = $this->execute("SHOW TABLES")->fetch_all();
         foreach ($rows as $row) {
            $tables[] = array_shift($row);
         }
         return $tables;
      }

      function fetch_attributes($table) {
         $attributes = array();
         $columns = $this->execute("DESCRIBE `$table`")->fetch_all();
         foreach ($columns as $column) {
            $attributes[] = $column['Field'];
         }
         return $attributes;
      }
   }

?>
