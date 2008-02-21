<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require LIB.'database/connection.php';
   require LIB.'database/mapper.php';
   require LIB.'database/active_record.php';
   require LIB.'database/association.php';

   function DB($model) {
      return ActiveRecordMapper::load($model);
   }

?>
