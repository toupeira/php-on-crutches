<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class DatabaseModel extends Model
   {
      static protected $table_attributes;

      protected $connection;
      protected $database = 'default';
      protected $table;
      protected $primary_key = 'id';

      protected $new_record = true;
      protected $load_attributes = true;

      function __construct($attributes=null) {
         if (empty($this->database)) {
            raise("No database set for model '".get_class($this)."'");
         } elseif (empty($this->table)) {
            raise("No table set for model '".get_class($this)."'");
         } elseif (empty($this->primary_key)) {
            raise("No primary key set for model '".get_class($this)."'");
         }

         if ($this->load_attributes and empty($this->attributes)) {
            $this->attributes = $this->table_attributes;
         }

         $this->set_attributes($attributes);
      }

      function get_connection() {
         if (!$this->connection) {
            $this->connection = DatabaseConnection::load($this->database);
         }

         return $this->connection;
      }

      # Load model attributes from database schema
      function get_table_attributes() {
         if ($attributes = self::$table_attributes[$this->table]) {
            return $attributes;
         } else {
            $attributes = array();
            $columns = $this->query("DESCRIBE {$this->table}")->fetchAll();
            foreach ($columns as $column) {
               $attributes[$column['Field']] = null;
            }
            return self::$table_attributes[$this->table] = $attributes;
         }
      }

      # Wrapper for database finders
      function load($attributes) {
         $this->attributes = $attributes;
         $this->new_record = false;
         return true;
      }

      # Called from DB::find
      function _find($id, $value=null) {
         if ($value) {
            $key = $id;
         } else {
            $key = $this->primary_key;
            $value = $id;
         }

         return $this->query(
            "SELECT * FROM {$this->table} WHERE $key = ? LIMIT 1", $value
         )->fetch_load($this);
      }

      # Called from DB::find_all
      function _find_all($key=null, $value=null) {
         if ($key and $value) {
            $condition = "WHERE $key = ?";
         }

         return $this->query(
            "SELECT * FROM {$this->table} $condition", $value
         )->fetch_all_load($this);
      }

      function exists() {
         return !$this->new_record;
      }

      function save() {
         if (!$this->is_valid()) {
            return false;
         }


         if ($this->exists()) {
            foreach ($this->attributes as $key => $value) {
               if ($key != $this->primary_key) {
                  $fields[] = "`$key` = ?";
                  $params[] = $value;
               }
            }

            $action = 'update';
            $query = "UPDATE %s SET %s WHERE {$this->primary_key} = ?";
            $params[] = $this->attributes[$this->primary_key];
         } else {
            foreach ($this->attributes as $key => $value) {
               $fields[] = '?';
               $params[] = $value;
            }

            $action = 'create';
            $query = "INSERT INTO %s VALUES (%s)";
         }

         $this->call_if_defined(before_save);
         $this->call_if_defined("before_$action");

         $this->query(
            sprintf($query, $this->table, implode(", ", $fields)),
            $params
         );

         if ($action == 'create') {
            $this->new_record = false;
            $this->attributes[$this->primary_key] = $this->connection->insert_id();
         }

         $this->call_if_defined("after_$action");
         $this->call_if_defined(after_save);

         return true;
      }

      function destroy() {
         if ($this->exists()) {
            $this->call_if_defined(before_destroy);
            $this->delete();
            $this->call_if_defined(after_destroy);
            return true;
         } else {
            return false;
         }
      }

      function delete() {
         if ($this->exists()) {
            $this->query(
               "DELETE FROM {$this->table} WHERE {$this->primary_key} = ?",
               $this->attributes[$this->primary_key]
            );
            return true;
         } else {
            return false;
         }
      }

      protected function query() {
         $args = func_get_args();
         return call_user_func_array(array($this->get_connection(), query), $args);
      }
   }

?>
