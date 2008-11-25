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
      protected $_model;
      protected $_related;
      protected $_options;

      function __construct($model, $related, $options=null) {
         $this->_model = $model;
         $this->_related = $related;
         $this->_options = (array) $options;
      }

      function get_model() {
         return $this->_model;
      }

      function get_related() {
         return $this->_related;
      }

      function get_options() {
         return $this->_options;
      }

      function get_key() {
         $table = DB($this->_related)->table;
         $key = foreign_key($this->_model);
         return "`$table`.`$key`";
      }

      function load(ActiveRecord $model) {
         return $this->load_data($model);
      }

      protected function load_data(ActiveRecord $model) {
         throw new ApplicationError("Association doesn't implement 'load_data'");
      }
   }

?>
