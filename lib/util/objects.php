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
      # Automatic getters
      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } else {
            $class = get_class($this);
            throw new ApplicationError("Call to undefined method $class::$getter()");
         }
      }

      # Automatic setters
      function __set($key, $value) {
         $setter = "set_$key";
         if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
         } else {
            $class = get_class($this);
            throw new ApplicationError("Call to undefined method $class::$setter()");
         }
      }

      # Call a function if it is defined
      function call_if_defined($method) {
         if (method_exists($this, $method)) {
            return $this->$method();
         }
      }

      # Call a function if it is defined, and raise an exception if it returns false
      function call_filter($filter) {
         if ($this->call_if_defined($filter) === false) {
            throw new ApplicationError("Filter '$filter' returned false");
         }
      }
   }

?>
