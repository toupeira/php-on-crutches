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
      protected $_attributes = array();

      # Changed attributes
      protected $_changed_attributes = array();

      # Cached attributes
      protected $_cache = array();

      # Read-only attributes can't be changed
      protected $_readonly = array();
      # Protected attributes can only be set explicitly
      protected $_protected = array();
      # Attributes which should not be automatically trimmed
      protected $_skip_trim = array();

      # List of error messages
      protected $_errors = array();

      function __construct(array $attributes=null, array $defaults=null) {
         $this->set_attributes($attributes, $defaults);
      }

      function __toString() {
         return parent::__toString($this->attributes);
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
         } elseif (array_key_exists($key, $this->_attributes)) {
            return $this->read_attribute($key);
         } else {
            throw new UndefinedMethod($this, $getter);
         }
      }

      function __set($key, $value) {
         if (in_array($key, $this->_readonly)) {
            throw new ApplicationError("Can't change read-only attribute '$key'");
         } else {
            $setter = "set_$key";
            if (is_string($value) and !in_array($key, $this->_skip_trim)) {
               $value = trim($value);
            }

            if (method_exists($this, $setter)) {
               $this->$setter(&$value);
            } elseif (array_key_exists($key, $this->_attributes)) {
               $this->write_attribute($key, $value);
            } else {
               throw new UndefinedMethod($this, $setter);
            }

            unset($this->_cache[$key]);
         }

         return $this;
      }

      # Wrappers for use in custom setters and getters

      function read_attribute($key) {
         return $this->_attributes[$key];
      }

      function write_attribute($key, $value) {
         $old_value = $this->read_attribute($key);
         $this->_attributes[$key] = &$value;

         if ($this->read_attribute($key) != $old_value) {
            $this->_changed_attributes[$key] = $old_value;
         }
         return $this;
      }

      function get_attributes() {
         return $this->_attributes;
      }

      function changed($key) {
         return array_key_exists($key, $this->_changed_attributes);
      }

      function get_changed() {
         return !empty($this->_changed_attributes);
      }

      function get_changes() {
         $changes = array();
         foreach ($this->_changed_attributes as $key => $old) {
            if (($new = $this->$key) != $old) {
               $changes[$key] = array($old, $new);
            }
         }

         return $changes;
      }

      function reset($key) {
         if (!is_null($old = $this->_changed_attributes[$key])) {
            unset($this->_changed_attributes[$key]);
            return $this->_attributes[$key] = $old;
         }

         return false;
      }

      # Set attributes
      function set_attributes(array $attributes=null, array $defaults=null) {
         if (is_array($defaults)) {
            $attributes = array_merge($defaults, (array) $attributes);
         }

         if (is_array($attributes) and !empty($attributes)) {
            array_delete($attributes, $this->_protected);
            foreach ($attributes as $key => $value) {
               $this->__set($key, $value);
            }

            return $this;
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
      function update(array $attributes) {
         if ($this->set_attributes($attributes) and $this->is_valid()) {
            return $this->save();
         } else {
            return false;
         }
      }

      # Generate cached property value with PHP code
      protected function cache($key, $code) {
         if (!isset($this->_cache[$key])) {
            $this->_cache[$key] = eval("return $code;");
         }

         return $this->_cache[$key];
      }

      # Error handling and validation

      function add_error($key, $message) {
         $this->_errors[$key][] = $message;
      }

      function get_errors() {
         return $this->_errors;
      }

      function is_valid() {
         $this->_errors = array();
         $this->validate();
         return empty($this->_errors);
      }

      protected function validate_attribute($key, $message, $valid) {
         if ($valid) {
            return true;
         } else {
            if (!in_array($key, $this->_errors)) {
               $this->add_error($key, _(humanize($key))." $message");
            }
            return false;
         }
      }

      # Validation checks

      protected function is_present($key) {
         return $this->validate_attribute($key,
            _("can't be blank"),
            !blank($this->_attributes[$key])
         );
      }

      protected function is_numeric($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("is not numeric"),
            ($allow_empty and $value == '') or is_numeric($value) or is_bool($value)
         );
      }

      protected function is_alpha($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("can only contain letters"),
            ($allow_empty and $value == '') or ctype_alpha($value)
         );
      }

      protected function is_alnum($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("can only contain alphanumeric characters"),
            ($allow_empty and $value == '') or preg_match('/^[\w\.-]*$/', $value)
         );
      }

      protected function is_email($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("is not a valid email address"),
            ($allow_empty and $value == '') or preg_match('/^[\w._%+-]+@([\w.-]+\.)+[a-z]{2,6}$/i', $value)
         );
      }

      protected function is_confirmed($key) {
         return $this->validate_attribute("{$key}_confirmation",
            _("doesn't match"),
            $this->_attributes[$key] == $this->_attributes["{$key}_confirmation"]
         );
      }

      protected function in_array($key, $array, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("is invalid"),
            ($allow_empty and $value == '') or in_array($value, $array)
         );
      }

      protected function has_length($key, $min=null, $max=null, $allow_empty=false) {
         $value = $this->_attributes[$key];
         $length = mb_strlen($value);

         if ($allow_empty and $value == '') {
            return true;
         }

         if (is_null($max)) {
            $message = sprintf(_("must be at least %d characters"), $min);
            $valid = ($length >= $min);
         } elseif ($min <= 0) {
            $message = sprintf(_("cannot be longer than %d characters"), $max);
            $valid = ($length <= $max);
         } else {
            $message = sprintf(_("must be between %d and %d characters"), $min, $max);
            $valid = ($length >= $min and $length <= $max);
         }

         return $this->validate_attribute($key, $message , $valid);
      }

      protected function has_format($key, $format) {
         $length = count($this->_attributes[$key]);
         return $this->validate_attribute($key,
            _("is invalid"),
            preg_match($format, $this->_attributes[$key])
         );
      }

      # Wrappers for form helpers

      function form_element($attribute, $tag, array $options=null) {
         $key = underscore(get_class($this))."[$attribute]";
         $value = any(array_delete($options, 'value'), $this->_attributes[$attribute]);

         $options['id'] = $this->get_dom_id($attribute);
         $options['force'] = true;
         if (isset($this->_errors[$attribute])) {
            $options['errors'] = true;
         }

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

      function label($key, $label=null, array $options=null) {
         return label($this->get_dom_id($key), any($label, humanize($key)));
      }

      function text_field($key, array $options=null) {
         return $this->form_element($key, 'text_field', $options);
      }

      function text_area($key, array $options=null) {
         return $this->form_element($key, 'text_area', $options);
      }

      function password_field($key, array $options=null) {
         return $this->form_element($key, 'password_field', $options);
      }

      function hidden_field($key, array $options=null) {
         return $this->form_element($key, 'hidden_field', $options);
      }

      function check_box($key, array $options=null) {
         return $this->form_element($key, 'check_box', $options);
      }

      function select_tag($key, $values, array $options=null) {
         $options['values'] = $values;
         return $this->form_element($key, 'select_tag', $options);
      }

      function date_field($key=null, array $options=null) {
         return $this->form_element($key, 'date_field', $options);
      }

      function get_dom_id($key=null) {
         if ($key) {
            return underscore(get_class($this)).($key ? "_$key" : '');
         } else {
            return underscore(get_class($this)).'-'.$this->id;
         }
      }
   }

?>
