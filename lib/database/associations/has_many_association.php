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
      function load() {
         return DB($this->class)->find_all($this->key, $this->model->id);
      }
   }

?>
