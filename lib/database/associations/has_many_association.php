<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class HasManyAssociation extends Association
   {
      protected function load_data(ActiveRecord $model) {
         return DB($this->related)->where($this->key, $model->id);
      }
   }

?>
