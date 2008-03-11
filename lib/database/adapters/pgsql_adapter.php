<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class PgsqlAdapter extends DatabaseConnection
   {
      function get_dsn($options) {
         return "pgsql:host={$options['hostname']} dbname={$options['database']}";
      }
   }

?>
