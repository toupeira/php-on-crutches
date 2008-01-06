<?
/*
  PHP on Crutches - Copyright (c) 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License. See COPYING for details.

  $Id$
*/

  abstract class Model extends Object
  {
    # Model attributes
    public $attributes = array();
    # Read-only attributes can't be changed
    public $readonly = array();
    # Protected attributes can only be set explicitly
    public $protected = array();

    # Data for the attributes
    protected $data = array();
    # Cached attributes
    protected $cache = array();

    # List of attributes with errors
    protected $errors = array();
    # List of error messages
    protected $messages = array();

    function __construct($data=null) {
      $this->update_attributes($data);
    }

    # Stubs for implementation-specific actions

    static function find($name) {
      raise("Model doesn't implement 'find'");
    }

    static function find_first() {
      raise("Model doesn't implement 'find_first'");
    }

    static function find_all() {
      raise("Model doesn't implement 'find_all'");
    }

    function save() {
      raise("Model doesn't implement 'save'");
    }

    # Automatic property accessors for model attributes

    function __get($key) {
      $getter = "get_$key";
      if (method_exists($this, $getter)) {
        return $this->$getter();
      } elseif (in_array($key, $this->attributes)) {
        return $this->data[$key];
      }
    }

    function __set($key, $value) {
      if (in_array($key, $this->readonly)) {
        raise("Can't update read-only attribute '$key'");
      } else {
        $setter = "set_$key";
        if (method_exists($this, $setter)) {
          $this->$setter(&$value);
        } elseif (in_array($key, $this->attributes)) {
          $this->data[$key] = &$value;
        }
        unset($this->cache[$key]);
      }
      return $this;
    }

    # Wrappers for use in custom setters and getters

    function read_attribute($key) {
      return $this->data[$key];
    }

    function write_attribute($key, $value) {
      $this->data[$key] = &$value;
      return $this;
    }

    # Update data and save
    function update($data) {
      if ($this->update_attributes($data) and $this->is_valid()) {
        return $this->save();
      } else {
        return false;
      }
    }

    # Load attributes from an array
    function update_attributes($data) {
      if (is_array($data)) {
        array_delete($data, $this->protected);
        foreach ($data as $key => $value) {
          $this->__set($key, $value);
        }
        return true;
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

    protected function add_error($keys, $message) {
      $this->errors = array_unique(array_merge($this->errors, (array) $keys));
      $this->messages[] = $message;
    }

    function get_errors() {
      return $this->errors;
    }

    function get_messages() {
      return $this->messages;
    }

    # 
    function validate() {}

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
        !empty($this->data[$key])
      );
    }

    protected function is_numeric($key) {
      return $this->validate_attribute($key,
        "is not numeric",
        is_numeric($this->data[$key])
      );
    }

    protected function is_alpha($key) {
      return $this->validate_attribute($key,
        "can only contain letters",
        ctype_alpha($this->data[$key])
      );
    }

    protected function is_alnum($key) {
      return $this->validate_attribute($key,
        "can only contain alphanumeric characters",
        preg_match('/^[\w\.-]*$/', $this->data[$key])
      );
    }

    protected function is_email($key) {
      return $this->validate_attribute($key,
        "is not a valid email address",
        preg_match('/^[\w\.\-\+]+@([\w]+\.)+[\w]+$/', $this->data[$key])
      );
    }

    protected function is_confirmed($key) {
      return $this->validate_attribute("{$key}_confirmation",
        "doesn't match",
        $this->data[$key] == $this->data["{$key}_confirmation"]
      );
    }

    protected function in_array($key, $array) {
      return $this->validate_attribute($key,
        "is invalid",
        in_array($this->data[$key], $array)
      );
    }

    protected function has_length($key, $min, $max, $empty=false) {
      $length = strlen($this->data[$key]);
      return $this->validate_attribute($key,
        "must be between $min and $max characters",
        ($empty and empty($this->data[$key])) or ($length >= $min and $length <= $max)
      );
    }

    protected function has_format($key, $format) {
      $length = count($this->data[$key]);
      return $this->validate_attribute($key,
        "is invalid",
        preg_match($format, $this->data[$key])
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
