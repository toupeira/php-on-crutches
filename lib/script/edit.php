#!/usr/bin/php5
<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../../config/environment.php';

   $_LOGGER->level = LOG_DISABLED;
   chdir(ROOT);

   $files = array();

   print "[1;34m>>>[0m ";

   if ($args = array_slice($argv, 1)) {
      $files = 
      print "editing [1m{$args[0]}[0m\n";
      $files = explode("\n", trim(`find app/controllers app/models app/helpers app/views public test/controllers test/models test/helpers -type f | grep -v '\.\(svn|swp|bak\)' | grep '{$args[0]}'`));
      $files == array('') and $files = null;
   } else {
      print "editing [1mapplication[0m\n";
      $app = config('application');
      $files = array(
         CONTROLLERS.'application_controller.php',
         HELPERS.'application_helper.php',
         VIEWS.'layouts/application.rhtml',
         STYLESHEETS.$app.'.css',
         JAVASCRIPTS.$app.'.css',
         CONFIG.'routes.php',
         CONFIG.'database.php',
         CONFIG.'framework.php',
         CONFIG.'application.php',
      );
   }

   if (empty($files)) {
      print "No files found.\n";
   } else {
      run($_ENV['EDITOR'].' '.implode(' ', $files));
   }

?>
