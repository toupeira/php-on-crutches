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
      function get_dsn() {
         return "pgsql:host={$this->options['hostname']} dbname={$this->options['database']}";
      }
   }

?>
