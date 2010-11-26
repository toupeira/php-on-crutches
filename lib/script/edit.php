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

   $filter = $argv[1];

   if (!$filter or $filter == 'app' or $filter =='application') {
      print "editing [1mapplication[0m\n";
      $files = array(
         CONTROLLERS.'application_controller.php',
         HELPERS.'application_helper.php',
         VIEWS.'layouts/application.thtml',
         CONFIG.'application.php',
      );
   } elseif ($filter == 'config') {
      print "editing [1mconfiguration[0m\n";
      $files = glob(CONFIG.'{,initializers/}*.php', GLOB_BRACE);

   } elseif (($model = classify($filter) and is_subclass_of($model, Model)) or
         ($controller = classify("{$filter}_controller") and is_subclass_of($controller, Controller))) {

      print "editing [1m".$filter."[0m\n";

      $singular = singularize($filter);
      $plural = pluralize($filter);

      $files = array_merge(
         array(
            CONTROLLERS."{$plural}_controller.php",
            HELPERS."{$plural}_helper.php",
         ),
         glob(MODELS."{$singular}*.php"),
         array(VIEWS.$plural)
      );

   } else {
      print "editing [1m".$filter."[0m\n";

      $files = find_files(array(
         CONTROLLERS,
         MODELS,
         HELPERS,
         VIEWS,
         WEBROOT,
         LIB,
         TEST.'controllers',
         TEST.'models',
         TEST.'helpers',
      ), "-type f -path '*$filter*' \\( -name '*.php' -o -name '*.thtml' \\)", false);
   }

   $files = array_select($files, file_exists);

   if (empty($files)) {
      print "No files found.\n";
   } else {
      # Reset locale
      putenv("LANG={$_ENV['LANG']}");
      putenv("LANGUAGE=");

      $files = array_map(escapeshellarg, $files);
      $editor = any($_ENV['EDITOR'], $_ENV['DISPLAY'] ? 'gvim' : 'vim');
      term_exec($editor.' '.implode(' ', $files));
   }

?>
