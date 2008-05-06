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
      protected $_key;

      function __construct($model, $related) {
         $this->_model = $model;
         $this->_related = $related;
         $this->_key = underscore($model).'_id';
      }

      function load(ActiveRecord $model) {
         return $this->load_data($model);
      }

      protected function load_data(ActiveRecord $model) {
         throw new ApplicationError("Association doesn't implement 'load_data'");
      }
   }

?>
