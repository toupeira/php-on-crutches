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
      protected function load_data(ActiveRecord $object) {
         $objects = DB($this->related)->where($this->key, $object->id);
         log_info("{$this->related} <=> {$this->model}");
         if (DB($this->related)->belongs_to($this->model)) {
            $objects->preload(array(underscore($this->model) => $object));
         }

         return $objects;
      }
   }

?>
