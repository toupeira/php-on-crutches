<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class ModelMapper extends Object
   {
      protected $_model;
      protected $_attributes;
      protected $_defaults;

      function __construct() {
         if (is_null($this->_model)) {
            $this->_model = substr(get_class($this), 0, -6);
         }
      }

      function __toString() {
         parent::__toString($this->_model);
      }

      function inspect() {
         return parent::inspect(array(
            'model' => $this->_model,
         ));
      }

      function get_model() {
         return $this->_model;
      }

      function get_attributes() {
         return (array) $this->_attributes;
      }

      function get_defaults() {
         return $this->_defaults;
      }

      function set_default($key, $value) {
         return $this->_defaults[$key] = $value;
      }

      # Helper to create and save a model
      function create(array $attributes, array $defaults=null) {
         $object = new $this->_model($attributes, $defaults);

         if ($object->save()) {
            return $object;
         } else {
            throw new ApplicationError(
               "Couldn't create {$this->_model} instance (".array_to_str($object->errors).")"
            );
         }
      }

      # Helper to find and destroy models
      function destroy($conditions) {
         $args = func_get_args();
         $status = false;
         foreach ($this->find_all($args) as $object) {
            $status = $object->destroy();
         }

         return $status;
      }

      # Stubs for mapper implementations

      function find($conditions) {
         throw new NotImplemented("Model mapper doesn't implement 'find'");
      }
      function find_all($conditions=null) {
         throw new NotImplemented("Model mapper doesn't implement 'find_all'");
      }

      function insert(array $attributes) {
         throw new NotImplemented("Model mapper doesn't implement 'insert'");
      }

      function update($conditions, array $attributes) {
         throw new NotImplemented("Model mapper doesn't implement 'update'");
      }

      function delete($conditions) {
         throw new NotImplemented("Model mapper doesn't implement 'delete'");
      }
   }

?>
