<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class BelongsToAssociation extends Association
   {
      function get_key() {
         return underscore($this->related).'_id';
      }

      protected function load_data(ActiveRecord $model) {
         if (!DB($this->model)->attributes[$this->key]) {
            throw new ApplicationError("Invalid foreign key '{$this->key}' for model {$this->model}");
         }

         if ($id = $model->{$this->key}) {
            return DB($this->related)->find($id);
         }
      }
   }

?>
