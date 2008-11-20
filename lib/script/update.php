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

   $force = false;
   $add_externals = false;

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-f': $force = true; break;
         case '-x': $add_externals = true; break;
         default:
            print "Usage: {$argv[0]}\n"
                  . "\n"
                  . "  -f        Automatically add new files\n"
                  . "  -x        Add default SVN externals\n"
                  . "\n";
               exit(255);
      }
   }

   function status($action, $path) {
      printf("%12s  %s\n", $action, str_replace(ROOT, '', $path));
   }

   function update_skeleton($skel, $force) {
      $path = ltrim(str_replace(LIB.'skeleton', '', $skel), '/');

      if (substr(basename($skel), 0, 1) == '.') {
         return;
      } elseif (is_dir($skel)) {
         if (!is_dir(ROOT.$path)) {
            status('create', $path);
            mkdir(ROOT.$path);
         }

         foreach (glob("$skel/*") as $skel) {
            update_skeleton($skel, $force);
         }
      } elseif (is_file($skel)) {
         if (!is_file(ROOT.$path)) {
            status('new', $path);
            if (!$force) {
               printf("%12s  %s", '', "Add? [y/n] ");
               $answer = strtolower(trim(fgets(STDIN)));
               if ($answer != 'y') {
                  return;
               }
            }
            status('create', $path);
            run("cp -p %s %s", $skel, ROOT.$path);
         } elseif (!run("diff -q %s %s", ROOT.$path, $skel)) {
            status('merge', $path);
            term_exec("vimdiff %s %s", ROOT.$path, $skel);
         }
      } else {
         print "Error: Invalid path $skel\n";
         exit(1);
      }
   }

   update_skeleton(LIB.'skeleton', $force);

   if (!is_dir(ROOT.'script')) {
      status('create', script);
      mkdir(ROOT.'script');
   }
   chdir(ROOT.'script');

   foreach (glob(LIB.'script/*.php') as $script) {
      $name = substr(basename($script), 0, -4);
      if (!file_exists($name)) {
         status('link', "script/$name");
         symlink("../lib/script/$name.php", $name);
      }
   }

   if ($add_externals) {
      print "  adding default SVN externals...\n";
      if (!is_dir('.svn')) {
         print "Error: '.' is not a working copy\n";
         exit(1);
      }

      rename('lib', 'lib.old');
      run("svn propset svn:externals %s .", "lib	http://dev.diarrhea.ch/svn/php-on-crutches/trunk/lib");
      run("svn propset svn:ignore '*' log");
      run("svn propset svn:ignore '*' tmp");
      run("svn propset svn:ignore 'all.*' public/javascripts public/stylesheets");
      term_exec("svn commit .");
      run("svn update .");
   }

?>
