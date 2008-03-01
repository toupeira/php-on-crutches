<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Association extends Object
   {
      protected $model;
      protected $class;
      protected $key;

      protected $data;
      protected $loaded = false;

      function __construct($model, $class) {
         $this->model = $model;
         $this->class = $class;
         $this->key = underscore(get_class($model)).'_id';
      }

      function load() {
         throw new ApplicationError("Association doesn't implement 'load'");
      }

      function get_data() {
         if ($this->loaded) {
            return $this->data;
         } else {
            $this->loaded = true;
            return $this->data = $this->load();
         }
      }

      function set_data($data) {
         $this->loaded = true;
         $this->data = &$data;
      }
   }

?>
