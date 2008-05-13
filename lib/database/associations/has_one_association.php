<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class HasOneAssociation extends Association
   {
      protected function load_data(ActiveRecord $model) {
         return DB($this->related)->find($this->key, $model->id);
      }
   }

?>
