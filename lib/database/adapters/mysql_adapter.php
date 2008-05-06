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
      function get_name() {
         return "{$this->_options['database']}@{$this->_options['hostname']}";
      }

      function get_dsn() {
         return "mysql:host={$this->_options['hostname']};dbname={$this->_options['database']}";
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

      function analyze_query($sql, array $params) {
         $queries = $this->execute("EXPLAIN $sql", $params);
         log_info("  Query details:");
         while ($query = $queries->fetch()) {
            $padding = str_repeat(' ', 7 + strlen($query['table']));

            $text = "   [1m*[0m [1;36m{$query['table']}[0m: "
                  . pluralize($query['rows'], 'row', 'rows')
                  . " [{$query['select_type']}] <{$query['type']}> ";

            if ($key = $query['key']) {
             $text .= "[1;35m{$key}[0m "
                    . "[0;35m({$query['possible_keys']})[0m\n$padding"
                    . "key length: {$query['key_len']} reference: {$query['ref']}  ";
            } else {
               $text .= "[1;31mno key[0m "
                    . "[0;35m({$query['possible_keys']})[0m";
            }

            if ($extra = $query['Extra']) {
               $text .= "\n$padding[1m$extra[0m";
            }

            log_info($text);
         }

         log_info('');
      }

   }

?>
