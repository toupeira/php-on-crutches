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
      function load($model) {
         return DB::find_all($this->class, "id_{$model->table}", $model->id);
      }
   }

?>
