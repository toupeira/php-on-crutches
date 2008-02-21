<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class ActiveRecordMapper extends Object
   {
      protected $connection;
      protected $database;
      protected $table;

      function __construct($model) {
         $this->database = $model->database;
         $this->table = $model->table;
      }

      function create($attributes) {
         foreach ($this->attributes as $key => $value) {
            $fields[] = '?';
            $params[] = $value;
         }

         $action = 'create';
         $query = "INSERT INTO `%s` VALUES (%s)";
         $this->query(
            sprintf($query, $this->table, implode(", ", $fields)),
            $params
         );

         return $this->connection->insert_id();
      }

      function update($id, $attributes) {
         foreach ($attributes as $key => $value) {
            if ($key != 'id') {
               $fields[] = "`$key` = ?";
               $params[] = $value;
            }
         }

         $query = sprintf("UPDATE `%s` SET %s WHERE `id` = ?",
                          $this->table, implode(", ", $fields));
         $params[] = $this->attributes['id'];
         $this->query($query, $params);

         return $id;
      }

      function delete($id) {
         $this->query(
            "DELETE FROM `{$this->table}` WHERE `id` = ?", $id
         );

         return $id;
      }

      function find_first() {
      }

      function find_all() {
      }

      function __call() {
      }

      function count() {
      }

      function get_connection() {
         if (!$this->connection) {
            $this->connection = DatabaseConnection::load($this->database);
         }

         return $this->connection;
      }

      function get_attributes() {
         return $this->get_connection()->get_table_attributes($this->table);
      }

      function query() {
         $args = func_get_args();
         return call_user_func_array(array($this->get_connection(), query), $args);
      }

      protected function build_select($options) {
      }

      protected function build_where($options) {
      }
   }

?>
