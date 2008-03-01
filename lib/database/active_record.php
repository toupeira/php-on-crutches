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
      protected $mapper;
      protected $database = 'default';
      protected $table;

      protected $new_record = true;
      protected $load_attributes = true;
      protected $changed_attributes;

      protected $associations;
      protected $has_one;
      protected $has_many;
      protected $belongs_to;

      function __construct($attributes=null, $defaults=null) {
         if (empty($this->database)) {
            throw new ConfigurationError("No database set for model '".get_class($this)."'");
         } elseif (empty($this->table)) {
            throw new ConfigurationError("No table set for model '".get_class($this)."'");
         }

         if ($this->load_attributes and empty($this->attributes)) {
            foreach ($this->get_mapper()->attributes as $key) {
               $this->attributes[$key] = null;
            }
         }

         $this->protected[] = 'id';
         $this->set_attributes($attributes, $defaults);
         $this->call_if_defined('associations');
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

      function get_mapper() {
         if (is_null($this->mapper)) {
            $this->mapper = DatabaseMapper::load($this);
         }

         return $this->mapper;
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

      function exists() {
         return !$this->new_record;
      }

      function save() {
         if (!$this->is_valid()) {
            return false;
         }

         if ($this->exists()) {
            $attributes = array_get($this->attributes, $this->changed_attributes);

            if (empty($attributes)) {
               return true;
            }

            $action = 'update';
            $args = array($this->id, $attributes);
         } else {
            $action = 'insert';
            $args = array($this->attributes);
         }

         $this->call_filter(before_save);
         $this->call_filter("before_$action");

         $id = call_user_func_array(array($this->get_mapper(), $action), $args);

         if ($action == 'create') {
            $this->new_record = false;
            $this->attributes['id'] = $id;
         }

         $this->changed_attributes = null;

         $this->call_filter("after_$action");
         $this->call_filter(after_save);

         return true;
      }

      function destroy() {
         if ($this->exists()) {
            $this->call_filter(before_destroy);
            $this->delete();
            $this->call_filter(after_destroy);
            return true;
         } else {
            return false;
         }
      }

      function delete() {
         if ($this->exists()) {
            $this->get_mapper()->delete($this->id);
            $this->new_record = true;
            $this->readonly = array_keys($this->attributes);
            return true;
         } else {
            return false;
         }
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
