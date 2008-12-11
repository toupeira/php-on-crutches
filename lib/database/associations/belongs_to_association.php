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

      protected function load_data(ActiveRecord $object) {
         if ($id = $object->{$this->key}) {
            $parent = DB($this->related)->find($id);
            if (DB($this->related)->has_one($this->model) or DB($this->related)->has_many($this->model)) {
               $key = underscore($this->model);
               if (!$parent->attributes[$key]) {
                  $parent->add_virtual($key, $object);
               }
            }

            return $parent;
         }
      }
   }

?>
