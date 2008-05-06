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
      protected function load_data(ActiveRecord $model) {
         $key = underscore($this->_related).'_id';
         if ($id = $model->$key) {
            return DB($this->_related)->find($id);
         }
      }
   }

?>
