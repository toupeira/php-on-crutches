<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Base class for all objects
   class Object
   {
      function __toString($id=null) {
         return $this->inspect($id);
      }

      function get_slug() {
         return parameterize($this->__toString());
      }

      function inspect($data=null) {
         if (is_array($data) and $data) {
            foreach ($data as $key => $value) {
               if ($value and !is_array($value) and $key != 'password') {
                  if (!is_numeric($value)) {
                     $value = '"'.str_replace("\n", " ", truncate($value, 30)).'"';
                  }
                  $values[] = "$key: $value";
               }
            }

            $data = ($values ? ' '.implode(', ', $values) : null);
         }

         return '#<'.get_class($this).($data ? $data : '').'>';
      }

      # Automatic getters
      function __get($key) {
         if (method_exists($this, $getter = "get_$key")) {
            return $this->$getter();
         } elseif (method_exists($this, $key)) {
            return $this->$key();
         } else {
            throw new UndefinedMethod($this, $key);
         }
      }

      # Automatic setters
      function __set($key, $value) {
         if (method_exists($this, $setter = "set_$key")) {
            $this->$setter($value);
            return $this;
         } else {
            throw new UndefinedMethod($this, $key);
         }
      }

      # Call a function if it is defined
      function call_if_defined($method, $args=null) {
         if (method_exists($this, $method)) {
            if (!is_array($args)) {
               $args = array_slice(func_get_args(), 1);
            }
            return call_user_func_array(array($this, $method), $args);
         }
      }

      # Call a function if it is defined, and raise an exception if it returns false
      function call_filter($filter, $args=null) {
         $args = array_slice(func_get_args(), 1);
         if ($this->call_if_defined($filter, $args) === false) {
            throw new ApplicationError("Filter '$filter' returned false");
         }
      }
   }

?>
