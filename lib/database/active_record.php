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
      protected $_mapper;

      protected $_new_record = true;
      protected $_load_attributes = true;
      protected $_virtual_attributes;

      function __construct(array $attributes=null, array $defaults=null) {
         # Always protect the ID from mass-assignments
         $this->_protected[] = $this->mapper->primary_key;

         # Load attributes from the database
         if ($this->_load_attributes and empty($this->_attributes)) {
            foreach ($this->mapper->attributes as $key => $options) {
               $this->_attributes[$key] = null;

               if ($default = $options['default']) {
                  $this->_attributes[$key] = $default;
               }
            }
         }

         # Add virtual attributes
         foreach ((array) $this->_virtual_attributes as $key) {
            $this->add_virtual($key, null);
         }

         # Set the default values
         $this->set_attributes($attributes, array_merge(
            (array) $this->mapper->defaults, (array) $defaults
         ));
      }

      function __get($key) {
         if (!array_key_exists($key, $this->_attributes) and
            $association = $this->mapper->associations[$key])
         {
            return $this->add_virtual($key, $association->load($this));
         } else {
            return parent::__get($key);
         }
      }

      function get_mapper() {
         if (is_null($this->_mapper)) {
            $this->_mapper = DatabaseMapper::load(get_class($this));
         }

         return $this->_mapper;
      }

      function get_id() {
         return $this->_attributes[$this->mapper->primary_key];
      }

      function get_new_record() {
         return $this->_new_record;
      }

      function add_virtual($key, $value) {
         $this->_virtual_attributes[] = $key;
         return $this->_attributes[$key] = $value;
      }

      # Wrapper for database finders
      function load(array $attributes) {
         foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $this->_attributes)) {
               # Add unknown column names as virtual attributes
               $this->add_virtual($key, $value);
            } else {
               $this->_attributes[$key] = $value;
            }
         }

         $this->_new_record = false;
         $this->_changed_attributes = array();

         return $this;
      }

      # Reload attributes from database
      function reload() {
         if (!$this->_new_record and !array_key_exists($this->mapper->primary_key, $this->_changed_attributes)) {
            return $this->load($this->mapper->find($this->id)->attributes);
         } else {
            return false;
         }
      }

      function save($force_update=false) {
         if (!$this->is_valid()) {
            return false;
         }

         if ($this->_new_record) {
            $action = 'create';
            $sql_action = 'insert';
         } else {
            $action = $sql_action = 'update';
         }

         $this->call_filter("before_$action");
         $this->call_filter(before_save);

         $attributes = array_get($this->_attributes,
            array_keys($this->_changed_attributes)
         );
         array_delete($attributes, $this->_virtual_attributes);

         if ($this->_new_record) {
            $args = array($attributes);
         } else {
            if (empty($attributes) and !$force_update) {
               return $this;
            }

            $args = array($attributes, $this->id, $force_update);
         }

         $id = call_user_func_array(array($this->mapper, $sql_action), $args);

         if ($action == 'create') {
            $this->_new_record = false;
            $this->_attributes[$this->mapper->primary_key] = $id;
         }

         $this->call_filter(after_save);
         $this->call_filter("after_$action");

         $this->_changed_attributes = array();

         return $this;
      }

      function destroy() {
         if ($this->_new_record) {
            return false;
         } else {
            $this->call_filter(before_destroy);
            $this->delete();
            $this->call_filter(after_destroy);
            return true;
         }
      }

      function delete() {
         if ($this->_new_record) {
            return false;
         } else {
            $this->mapper->delete($this->id);
            $this->_new_record = true;
            $this->_readonly = array_keys($this->attributes);
            return true;
         }
      }

      # Automatic form fields based on database schema
      function auto_field($key) {
         $args = func_get_args();

         if (!$column = $this->mapper->attributes[$key]) {
            throw new ValueError("Invalid attribute '$key'");
         }

         $options = array();

         switch (array_shift(explode(' ', $column['type'], 2))) {
            case 'integer':
            case 'float':
               $method = 'text_field';
               break;
            case 'bool':
               $method = 'check_box';
               break;
            case 'string':
               $method = 'text_field';
               $options['maxlength'] = $column['size'];
               $options['size'] = min(40, $column['size']);
               break;
            case 'text':
               $method = 'text_area';
               $options['cols'] = 84;
               $options['rows'] = 12;
               break;
            case 'date':
               $method = 'date_field';
               break;
            case 'datetime';
               $method = 'text_field';
               $options['size'] = $options['maxlength'] = strlen($this->$key);
               break;
            default:
               throw new NotImplemented("Unsupported column type '{$column['type']}'");
         }

         $args[] = $options;

         return call_user_func_array(array($this, $method), $args);
      }

      # Database specific validation checks

      function is_valid() {
         $this->call_filter('before_validation');
         parent::is_valid();

         foreach ($this->mapper->attributes as $key => $options) {
            if (!$options['key'] and !$this->_errors[$key]) {
               if ($key == 'email' and !$this->is_email($key)) {
                  continue;
               }

               if (!$options['null'] and is_null($options['default']) and
                  !$this->is_present($key)) {
                  continue;
               }

               if ($options['unique'] and !$this->is_unique($key)) {
                  continue;
               }

               if (($options['type'] == 'integer' or $options['type'] == 'float') and
                  !$this->is_numeric($key, true)) {
                  continue;
               }

               if ($options['size'] > 0 and in_array($options['type'], array('string', 'text'))) {
                  $this->has_length($key, 0, $options['size']);
               }
            }
         }

         $this->call_filter('after_validation');
         return empty($this->_errors);
      }

      protected function is_unique($key) {
         $objects = $this->mapper->where($key, $this->$key);
         if (!$this->_new_record) {
            $objects->where("`{$this->mapper->primary_key}` != ?", $this->id);
         }

         return $this->validate_attribute($key,
            _("already exists"),
            $objects->count() == 0
         );
      }
   }

?>
