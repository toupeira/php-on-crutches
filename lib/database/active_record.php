<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class ActiveRecord extends Model
   {
      protected $connection;
      protected $database = 'default';
      protected $table;

      protected $new_record = true;
      protected $load_attributes = true;
      protected $changed_attributes;

      protected $associations;
      protected $has_one;
      protected $has_many;
      protected $belongs_to;

      function __construct($attributes=null) {
         if (empty($this->database)) {
            raise("No database set for model '".get_class($this)."'");
         } elseif (empty($this->table)) {
            raise("No table set for model '".get_class($this)."'");
         }

         if ($this->load_attributes and empty($this->attributes)) {
            $columns = $this->get_connection()->get_table_attributes($this->table);
            foreach ($columns as $column) {
               $this->attributes[$column] = null;
            }
         }

         $this->protected[] = 'id';
         $this->set_attributes($attributes);
         $this->add_associations();
      }

      function __get($key) {
         if ($association = $this->associations[$key]) {
            return $association->data;
         } else {
            return parent::__get($key);
         }
      }

      function __set($key, $value) {
         $old_value = $this->__get($key);
         parent::__set($key, $value);

         if ($this->__get($key) != $old_value) {
            $this->changed_attributes[] = $key;
         }
         return $this;
      }

      function get_connection() {
         if (!$this->connection) {
            $this->connection = DatabaseConnection::load($this->database);
         }

         return $this->connection;
      }

      function get_database() {
         return $this->database;
      }

      function get_table() {
         return $this->table;
      }

      # Wrapper for database finders
      function load($attributes) {
         $this->attributes = $attributes;
         $this->new_record = false;
         $this->changed_attributes = null;
         return true;
      }

      # Called from DB::find
      function _find($id, $value=null) {
         if ($value) {
            $key = $id;
         } else {
            $key = 'id';
            $value = $id;
         }

         return $this->query(
            "SELECT * FROM `{$this->table}` WHERE `$key` = ? LIMIT 1", $value
         )->fetch_load($this);
      }

      # Called from DB::find_all
      function _find_all($key=null, $value=null) {
         if ($key and $value) {
            $condition = " WHERE `$key` = ?";
         }

         return $this->query(
            "SELECT * FROM `{$this->table}`$condition", (array) $value
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
            if (empty($this->changed_attributes)) {
               return true;
            }

            foreach ($this->attributes as $key => $value) {
               if ($key != 'id' and in_array($key, (array) $this->changed_attributes)) {
                  $fields[] = "`$key` = ?";
                  $params[] = $value;
               }
            }

            $action = 'update';
            $query = "UPDATE `%s` SET %s WHERE `id` = ?";
            $params[] = $this->attributes['id'];
         } else {
            foreach ($this->attributes as $key => $value) {
               $fields[] = '?';
               $params[] = $value;
            }

            $action = 'create';
            $query = "INSERT INTO `%s` VALUES (%s)";
         }

         $this->call_if_defined(before_save);
         $this->call_if_defined("before_$action");

         $this->query(
            sprintf($query, $this->table, implode(", ", $fields)),
            $params
         );

         if ($action == 'create') {
            $this->new_record = false;
            $this->attributes['id'] = $this->connection->insert_id();
         }

         $this->changed_attributes = null;

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
               "DELETE FROM `{$this->table}` WHERE `id` = ?",
               $this->attributes['id']
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

      protected function add_associations() {
         foreach (array('has_one', 'has_many', 'has_many_through', 'belongs_to') as $type) {
            if (!empty($this->$type)) {
               require_once LIB."database/associations/{$type}_association.php";
               $association = camelize($type).'Association';
               foreach ($this->$type as $key => $class) {
                  $this->associations[$key] = new $association($this, $class);
               }
            }
         }
      }
   }

?>
