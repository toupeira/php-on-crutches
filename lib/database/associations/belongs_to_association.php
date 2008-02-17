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
      function load() {
         $key = underscore($this->class).'_id';
         return DB::find_all($this->class, 'id', $this->model->$key);
      }
   }

?>
