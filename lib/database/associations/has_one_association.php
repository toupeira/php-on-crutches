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
      protected function load_data(ActiveRecord $object) {
         $child = DB($this->related)->find($this->key, $object->id);
         if (DB($this->related)->belongs_to($this->model)) {
            $key = underscore($this->model);
            if (!$child->attributes[$key]) {
               $child->add_virtual($key = $object);
            }
         }

         return $child;
      }
   }

?>
