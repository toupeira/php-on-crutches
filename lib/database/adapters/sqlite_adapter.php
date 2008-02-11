<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class SqliteAdapter extends DatabaseConnection
   {
      function fetch_tables() {
         $tables = array();
         $rows = $this->query(
            "SELECT name FROM sqlite_master WHERE type='table'"
         )->fetch_all();
         foreach ($rows as $row) {
            $tables[] = array_shift($row);
         }
         return $tables;
      }

      function fetch_attributes($table) {
         $attributes = array();
         $columns = $this->query("PRAGMA table_info(`$table`)")->fetch_all();
         foreach ($columns as $column) {
            $attributes[] = $column['name'];
         }
         return $attributes;
      }
   }

?>
