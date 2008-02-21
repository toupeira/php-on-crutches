<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Model extends Object
   {
      # Model attributes
      protected $attributes = array();
      # Cached attributes
      protected $cache = array();

      # Read-only attributes can't be changed
      public $readonly = array();
      # Protected attributes can only be set explicitly
      public $protected = array();

      # List of attributes with errors
      protected $errors = array();
      # List of error messages
      protected $messages = array();

      function __construct($attributes=null) {
         $this->set_attributes($attributes);
      }

      # Stubs for implementation-specific actions

      static function find($name) {
         raise("Model doesn't implement 'find'");
      }

      static function find_all() {
         raise("Model doesn't implement 'find_all'");
      }

      function save() {
         raise(get_class()." doesn't implement 'save'");
      }

      function destroy($name) {
         raise(get_class()." doesn't implement 'destroy'");
      }

      function validate() {}

      # Automatic property accessors for model attributes

      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } elseif (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
         } else {
            $class = get_class($this);
            raise("Call to undefined method $class::$getter()");
         }
      }

      function __set($key, $value) {
         if (in_array($key, $this->readonly)) {
            raise("Can't update read-only attribute '$key'");
         } else {
            $setter = "set_$key";
            if (method_exists($this, $setter)) {
               $this->$setter(&$value);
            } elseif (array_key_exists($key, $this->attributes)) {
               $this->attributes[$key] = &$value;
            } else {
               $class = get_class($this);
               raise("Call to undefined method $class::$setter()");
            }
            unset($this->cache[$key]);
         }
         return $this;
      }

      # Wrappers for use in custom setters and getters

      function read_attribute($key) {
         return $this->attributes[$key];
      }

      function write_attribute($key, $value) {
         $this->attributes[$key] = &$value;
         return $this;
      }

      function get_attributes() {
         return $this->attributes;
      }

      # Set attributes
      function set_attributes($attributes) {
         if (is_array($attributes) and !empty($attributes)) {
            array_delete($attributes, $this->protected);
            foreach ($attributes as $key => $value) {
               $this->__set($key, $value);
            }
            return true;
         } else {
            return false;
         }
      }

      # Update attributes and save
      function update($attributes) {
         if ($this->set_attributes($attributes) and $this->is_valid()) {
            return $this->save();
         } else {
            return false;
         }
      }

      # Generate cached property value with PHP code
      protected function cache($key, $code) {
         if (!isset($this->cache[$key])) {
            $this->cache[$key] = eval("return $code;");
         }

         return $this->cache[$key];
      }

      # Error handling and validation

      function add_error($keys, $message) {
         $this->errors = array_unique(array_merge($this->errors, (array) $keys));
         $this->messages[] = $message;
      }

      function get_errors() {
         return $this->errors;
      }

      function get_messages() {
         return $this->messages;
      }

      function is_valid() {
         $this->validate();
         return empty($this->errors) && empty($this->messages);
      }

      protected function validate_attribute($key, $message, $value) {
         if ($value) {
            return true;
         } else {
            if (!in_array($key, $this->errors)) {
               $this->add_error($key, humanize($key)." $message");
            }
            return false;
         }
      }

      # Validation checks

      protected function is_present($key) {
         return $this->validate_attribute($key,
            "can't be blank",
            !empty($this->attributes[$key])
         );
      }

      protected function is_numeric($key) {
         return $this->validate_attribute($key,
            "is not numeric",
            is_numeric($this->attributes[$key])
         );
      }

      protected function is_alpha($key) {
         return $this->validate_attribute($key,
            "can only contain letters",
            ctype_alpha($this->attributes[$key])
         );
      }

      protected function is_alnum($key) {
         return $this->validate_attribute($key,
            "can only contain alphanumeric characters",
            preg_match('/^[\w\.-]*$/', $this->attributes[$key])
         );
      }

      protected function is_email($key) {
         return $this->validate_attribute($key,
            "is not a valid email address",
            preg_match('/^[\w\.\-\+]+@([\w]+\.)+[\w]+$/', $this->attributes[$key])
         );
      }

      protected function is_confirmed($key) {
         return $this->validate_attribute("{$key}_confirmation",
            "doesn't match",
            $this->attributes[$key] == $this->attributes["{$key}_confirmation"]
         );
      }

      protected function in_array($key, $array) {
         return $this->validate_attribute($key,
            "is invalid",
            in_array($this->attributes[$key], $array)
         );
      }

      protected function has_length($key, $min, $max, $empty=false) {
         $length = strlen($this->attributes[$key]);
         return $this->validate_attribute($key,
            "must be between $min and $max characters",
            ($empty and empty($this->attributes[$key])) or ($length >= $min and $length <= $max)
         );
      }

      protected function has_format($key, $format) {
         $length = count($this->attributes[$key]);
         return $this->validate_attribute($key,
            "is invalid",
            preg_match($format, $this->attributes[$key])
         );
      }

      # Wrappers for form helpers

      function form_element($tag, $key, $options=null) {
         if (in_array($key, $this->errors)) {
            $options['errors'] = true;
         }

         $value = $this->__get($key);
         $key = underscore(get_class($this))."[$key]";

         switch ($tag) {
            case 'check_box':
               return check_box($key, 'on', $value, $options);
            case 'select_tag':
               return select_tag($key, array_delete($options, 'values'), $value, $options);
            default:
               return $tag($key, $value, $options);
         }
      }

      function text_field($key, $options=null) {
         return $this->form_element('text_field', $key, $options);
      }

      function text_area($key, $options=null) {
         return $this->form_element('text_area', $key, $options);
      }

      function password_field($key, $options=null) {
         return $this->form_element('password_field', $key, $options);
      }

      function check_box($key, $options=null) {
         return $this->form_element('check_box', $key, $options);
      }

      function select_tag($key, $values, $options=null) {
         $options['values'] = $values;
         return $this->form_element('select_tag', $key, $options);
      }
   }

?>
