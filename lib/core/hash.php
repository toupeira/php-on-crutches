<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class Hash extends Object implements ArrayAccess
   {
      protected $_data;

      function __construct($data=null) {
         $this->_data = (is_array($data) ? $data : func_get_args());
      }

      function inspect() {
         return parent::inspect($this->_data);
      }

      function __get($key) {
         if (method_exists($this, $getter = "get_$key")) {
            return $this->$getter();
         } else {
            return $this->_data[$key];
         }
      }

      function __set($key, $value) {
         return $this->_data[$key] = &$value;
      }

      function get_entries() {
         return (array) $this->_data;
      }

      function get_keys() {
         return array_keys((array) $this->_data);
      }

      function get_values() {
         return array_values((array) $this->_data);
      }

      function fetch($key) {
         if (isset($this->_data[$key])) {
            return $this->_data[$key];
         } else {
            throw new ValueError("Invalid key '$key'");
         }
      }

      function delete($key) {
         $value = $this->_data[$key];
         unset($this->_data[$key]);
         return $value;
      }

      # ArrayAccess implementation

      function offsetExists($key) {
         return isset($this->_data[$key]);
      }

      function offsetGet($key) {
         return $this->__get($key);
      }

      function offsetSet($key, $value) {
         return $this->__set($key, &$value);
      }

      function offsetUnset($key) {
         return $this->delete($key);
      }
   }

?>
