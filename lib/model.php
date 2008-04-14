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
      # Changed attributes
      protected $changed_attributes;
      # Cached attributes
      protected $cache = array();

      # Read-only attributes can't be changed
      public $readonly = array();
      # Protected attributes can only be set explicitly
      public $protected = array();

      # List of error messages
      protected $errors = array();

      function __construct($attributes=null, $defaults=null) {
         $this->set_attributes($attributes, $defaults);
      }

      # Stubs for implementation-specific actions

      static function find($name) {
         throw new NotImplemented("Model doesn't implement 'find'");
      }

      static function find_all() {
         throw new NotImplemented("Model doesn't implement 'find_all'");
      }

      function save() {
         throw new NotImplemented(get_class()." doesn't implement 'save'");
      }

      function destroy($name) {
         throw new NotImplemented(get_class()." doesn't implement 'destroy'");
      }

      function validate() {}

      # Automatic property accessors for model attributes

      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } elseif (array_key_exists($key, $this->attributes)) {
            return $this->read_attribute($key);
         } else {
            $class = get_class($this);
            throw new ApplicationError("Call to undefined method $class#$getter()");
         }
      }

      function __set($key, $value) {
         if (in_array($key, $this->readonly)) {
            throw new ApplicationError("Can't change read-only attribute '$key'");
         } else {
            $setter = "set_$key";
            if (method_exists($this, $setter)) {
               $this->$setter(&$value);
            } elseif (array_key_exists($key, $this->attributes)) {
               $this->write_attribute($key, $value);
            } else {
               $class = get_class($this);
               throw new ApplicationError("Call to undefined method $class#$setter()");
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
         $old_value = $this->read_attribute($key);
         $this->attributes[$key] = &$value;

         if ($this->read_attribute($key) != $old_value) {
            $this->changed_attributes[] = $key;
         }
         return $this;
      }

      function get_attributes() {
         return $this->attributes;
      }

      function get_changed() {
         return !empty($this->changed_attributes);
      }

      # Set attributes
      function set_attributes($attributes, $defaults=null) {
         if (is_array($defaults)) {
            $attributes = array_merge($defaults, (array) $attributes);
         }

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

      # Set an attribute only if it isn't set yet
      function set_default($key, $value) {
         if ($this->read_attribute($key) === null) {
            return $this->write_attribute($key, $value);
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

      function add_error($key, $message) {
         $this->errors[$key][] = $message;
      }

      function get_errors() {
         return $this->errors;
      }

      function is_valid() {
         $this->errors = array();
         $this->validate();
         return empty($this->errors);
      }

      protected function validate_attribute($key, $message, $value) {
         if ($value) {
            return true;
         } else {
            if (!in_array($key, $this->errors)) {
               $this->add_error($key, _(humanize($key))." $message");
            }
            return false;
         }
      }

      # Validation checks

      protected function is_present($key) {
         return $this->validate_attribute($key,
            _("can't be blank"),
            !blank($this->attributes[$key])
         );
      }

      protected function is_numeric($key, $allow_empty=false) {
         $value = $this->attributes[$key];
         return $this->validate_attribute($key,
            _("is not numeric"),
            ($allow_empty and empty($value)) or is_numeric($value)
         );
      }

      protected function is_alpha($key, $allow_empty=false) {
         $value = $this->attributes[$key];
         return $this->validate_attribute($key,
            _("can only contain letters"),
            ($allow_empty and empty($value)) or ctype_alpha($value)
         );
      }

      protected function is_alnum($key, $allow_empty=false) {
         $value = $this->attributes[$key];
         return $this->validate_attribute($key,
            _("can only contain alphanumeric characters"),
            ($allow_empty and empty($value)) or preg_match('/^[\w\.-]*$/', $value)
         );
      }

      protected function is_email($key, $allow_empty=false) {
         return $this->validate_attribute($key,
            _("is not a valid email address"),
            ($allow_empty and empty($value)) or preg_match('/^[\w\.\-\+]+@([\w]+\.)+[\w]+$/', $this->attributes[$key])
         );
      }

      protected function is_confirmed($key) {
         return $this->validate_attribute("{$key}_confirmation",
            _("doesn't match"),
            $this->attributes[$key] == $this->attributes["{$key}_confirmation"]
         );
      }

      protected function in_array($key, $array, $allow_empty=false) {
         return $this->validate_attribute($key,
            _("is invalid"),
            ($allow_empty and empty($value)) or in_array($this->attributes[$key], $array)
         );
      }

      protected function has_length($key, $min, $max, $allow_empty=false) {
         $length = strlen($this->attributes[$key]);
         return $this->validate_attribute($key,
            sprintf(_("must be between %d and %d characters"), $min, $max),
            ($allow_empty and empty($this->attributes[$key])) or ($length >= $min and $length <= $max)
         );
      }

      protected function has_format($key, $format) {
         $length = count($this->attributes[$key]);
         return $this->validate_attribute($key,
            _("is invalid"),
            preg_match($format, $this->attributes[$key])
         );
      }

      # Wrappers for form helpers

      function form_element($tag, $key, $options=null) {
         if (isset($this->errors[$key])) {
            $options['errors'] = true;
         }

         $value = $this->__get($key);
         $key = underscore(get_class($this))."[$key]";

         switch ($tag) {
            case 'check_box':
               return hidden_field($key, '0')
                    . check_box($key, '1', $value, $options);
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
