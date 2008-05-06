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

   function DB($name='default') {
      if (ctype_upper($name[0]) or is_object($name)) {
         return DatabaseMapper::load($name);
      } else {
         return DatabaseConnection::load($name);
      }
   }

?>
