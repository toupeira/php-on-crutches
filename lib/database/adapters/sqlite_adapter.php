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
      function get_name() {
         return basename($this->options['database']);
      }

      function get_dsn() {
         return "sqlite:{$this->options['database']}";
      }

      function get_timestamp() {
         return "current_timestamp";
      }

      function fetch_tables() {
         $tables = array();
         $rows = $this->execute(
            "SELECT `name` FROM `sqlite_master` WHERE type='table'"
         )->fetch_all();
         foreach ($rows as $row) {
            $tables[] = array_shift($row);
         }
         return $tables;
      }

      function fetch_attributes($table) {
         $attributes = array();
         $columns = $this->execute("PRAGMA table_info(`$table`)")->fetch_all();
         foreach ($columns as $column) {
            list($type, $size) = $this->parse_type($column['type']);

            if ($type == 'date' or $type == 'time') {
               $default = null;
               $has_default = !empty($column['dflt_value']);
            } else {
               $default = $column['dflt_value'];
               $has_default = !empty($default);
            }

            $attributes[$column['name']] = array(
               'key'         => (bool) $column['pk'],
               'type'        => $type,
               'size'        => $size,
               'null'        => $column['notnull'] == 0,
               'default'     => $default,
               'has_default' => $has_default,
            );
         }

         $indices = $this->execute("PRAGMA index_list(`$table`)")->fetch_all();
         foreach ($indices as $index) {
            if ($index['unique']) {
               if ($info = $this->execute("PRAGMA index_info(`{$index['name']}`)")->fetch()) {
                  $attributes[$info['name']]['unique'] = true;
               } else {
                  throw new ApplicationError("Invalid index '{$index['name']}'");
               }
            }
         }

         return $attributes;
      }
   }

?>
