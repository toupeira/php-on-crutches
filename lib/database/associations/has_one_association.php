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
      function load() {
         return DB($this->class)->find($this->key, $this->model->id);
      }
   }

?>
