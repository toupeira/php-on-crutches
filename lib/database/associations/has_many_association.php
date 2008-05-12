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
         if (!DB($this->related)->attributes[$this->key]) {
            throw new ApplicationError("Invalid foreign key '{$this->key}' for model {$this->related}");
         }

         return DB($this->related)->where($this->key, $model->id);
      }
   }

?>
