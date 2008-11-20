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

   # Helper function to quickly access model mappers and database connections.
   #
   # *Arguments:*
   #
   # * @$name@: specifies the model name if uppercase, or else the database name
   #
   # *Examples:*
   #
   # * @DB(Person)->find(1)@
   # * @DB(Person)->find_by_name('bob')@
   # * @DB('people')->execute('SELECT * FROM people WHERE name = ?', 'bob')@
   # * @DB()->get_tables()@
   #
   function DB($name='default') {
      if (ctype_upper($name[0]) or is_object($name)) {
         return DatabaseMapper::load($name);
      } else {
         return DatabaseConnection::load($name);
      }
   }

?>
