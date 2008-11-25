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
         return foreign_key($this->related);
      }

      protected function load_data(ActiveRecord $model) {
         if ($id = $model->{$this->key}) {
            return DB($this->related)->find($id);
         }
      }
   }

?>
