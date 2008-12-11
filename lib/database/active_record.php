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
      protected $_load_attributes = true;
      protected $_booleans = array();

      function __construct(array $attributes=null, array $defaults=null) {
         # Load attributes from the database
         if ($this->_load_attributes and empty($this->_attributes)) {
            foreach ($this->mapper->attributes as $key => $options) {
               $this->_attributes[$key] = null;

               if ($default = $options['default']) {
                  $this->_attributes[$key] = $default;
               }

               if ($options['type'] == 'bool') {
                  $this->_booleans[] = $key;
               }
            }
         }

         # Set the default values
         parent::__construct(array_merge(
            (array) $this->mapper->defaults,
            (array) $defaults,
            (array) $attributes
         ));
      }

      function get_slug() {
         $slug = parent::get_slug();
         return intval($this->id).($slug ? "-$slug" : '');
      }

      function __get($key) {
         if (!array_key_exists($key, $this->_attributes) and
            $association = $this->mapper->associations[$key])
         {
            if ($data = $association->load($this)) {
               return $this->add_virtual($key, $data);
            }
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

      function read_attribute($key) {
         if (in_array($key, $this->_booleans)) {
            return (bool) $this->_attributes[$key];
         } else {
            return $this->_attributes[$key];
         }
      }

      function write_attribute($key, $value) {
         if (substr($key, -3) == '_id' and is_object($value)) {
            $value = $value->id;
         }

         return parent::write_attribute($key, $value);
      }

      # Protect the ID for existing records
      function set_attributes(array $attributes=null) {
         if ($this->exists) {
            unset($attributes[$this->mapper->primary_key]);
         }

         return parent::set_attributes($attributes);
      }

      # Increase an attribute
      function grow($key, $step=1) {
         $this->$key += $step;
         return $this->mapper->update($this, array("`$key` = `$key` + ?" => $step));
      }

      # Decrease an attribute
      function shrink($key, $step=1) {
         $this->$key -= $step;
         return $this->mapper->update($this, array("`$key` = `$key` - ?" => $step));
      }

      # Generate automatic form fields based on database schema
      function auto_field($key) {
         $args = func_get_args();

         if (in_array($key, (array) $this->_virtual_attributes)) {
            return call_user_func_array(array(parent, 'auto_field'), $args);
         } elseif (!$column = $this->mapper->attributes[$key]) {
            throw new ValueError("Invalid attribute '$key'");
         }

         if ($column['key'] or $this->_frozen or in_array($key, $this->_readonly)) {
            return h($this->$key);
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
               if (substr($key, 0, 8) == 'password') {
                  $method = 'password_field';
               } else {
                  $method = 'text_field';
               }
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
            case 'time';
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

         $this->_errors = array();

         foreach ($this->mapper->attributes as $key => $options) {
            if (!$options['key'] and !$this->_errors[$key]) {
               if ($key == 'email' and !$this->is_email($key)) {
                  continue;
               }

               if (!$options['null'] and !$options['has_default'] and
                   !$options['type'] != 'bool' and !$this->is_present($key)) {
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

         $this->validate();

         $this->call_filter('after_validation');
         return empty($this->_errors);
      }

      protected function is_unique($key, $filter=null, $message=null) {
         if (!$this->changed($key)) {
            return true;
         }

         $objects = $this->mapper->where($key, $this->$key);

         if ($filter) {
            $objects->where($filter);
         }

         if (!$this->_new_record) {
            $objects->where("`{$this->mapper->primary_key}` != ?", $this->id);
         }

         return $this->validate_attribute($key,
            $objects->count() == 0,
            any($message, _("%s already exists"))
         );
      }
   }

?>
