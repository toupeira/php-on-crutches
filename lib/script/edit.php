#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../script.php';

   chdir(ROOT);

   print "[1;34m>>>[0m ";

   if ($filter = $argv[1]) {
      print "editing [1m".$filter."[0m\n";
      $files = find_files(array(
         CONTROLLERS,
         MODELS,
         HELPERS,
         VIEWS,
         WEBROOT,
         TEST.'controllers',
         TEST.'models',
         TEST.'helpers',
      ), "-type f -path '*$filter*' \\( -name '*.php' -o -name '*.thtml' \\)", false);
   } else {
      print "editing [1mapplication[0m\n";
      $files = array(
         CONTROLLERS.'application_controller.php',
         HELPERS.'application_helper.php',
         VIEWS.'layouts/application.thtml',
         CONFIG.'application.php',
      );
   }

   if (empty($files)) {
      print "No files found.\n";
   } else {
      # Reset locale
      putenv("LANG={$_ENV['LANG']}");
      putenv("LANGUAGE=");

      $files = array_map('escapeshellarg', $files);
      $editor = any($_ENV['EDITOR'], $_ENV['DISPLAY'] ? 'gvim' : 'vim');
      term_exec($editor.' '.implode(' ', $files));
   }

?>
