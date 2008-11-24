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
   $add_ignores = false;
   $add_externals = false;
   $diff = false;

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-f': $force = true; break;
         case '-d': $diff = true; break;
         case '-i': $add_ignores = true; break;
         case '-s': $add_externals = true; break;
         default:
            print "Usage: {$argv[0]}\n"
                  . "\n"
                  . "  -f        Automatically add new files\n"
                  . "  -d        Merge changes using vimdiff\n"
                  . "  -i        Add default SVN ignores\n"
                  . "  -x        Add /lib as SVN externals\n"
                  . "\n";
               exit(255);
      }
   }

   function status($action, $path) {
      printf("%12s  %s\n", $action, str_replace(ROOT, '', $path));
   }

   function ask($question) {
      printf("%12s  %s", '', "$question [y/n] ");
      return strtolower(trim(fgets(STDIN))) == 'y';
   }

   function update_skeleton($skel, $diff=false, $force=false) {
      $path = ltrim(str_replace(LIB.'skeleton', '', $skel), '/');

      if (substr(basename($skel), 0, 1) == '.') {
         return;
      } elseif (is_link($skel)) {
         if (!file_exists(ROOT.$path)) {
            status('new', $path);
            if ($force or ask('Link?')) {
               status('link', $path);
               $target = readlink($skel);
               chdir(ROOT.dirname($path));
               symlink($target, basename($path));
               chdir(ROOT);
            }
         }
      } elseif (is_dir($skel)) {
         if (!is_dir(ROOT.$path)) {
            status('create', $path);
            mkdir(ROOT.$path);
         }

         foreach (scandir($skel) as $path) {
            update_skeleton("$skel/$path", $diff, $force);
         }
      } elseif (is_file($skel)) {
         if (!is_file(ROOT.$path)) {
            status('new', $path);
            if ($force or ask('Add?')) {
               status('create', $path);
               run("cp -p %s %s", $skel, ROOT.$path);
            }
         } elseif ($diff and !run("diff -q %s %s", ROOT.$path, $skel)) {
            status('merge', $path);
            term_exec("vimdiff %s %s", ROOT.$path, $skel);
         } else {
            status('exists', $path);
         }
      } else {
         print "Error: Invalid path $skel\n";
         exit(1);
      }
   }

   update_skeleton(LIB.'skeleton', $diff, $force);

   chdir(ROOT);

   if ($add_ignores) {
      if (!is_dir('.svn')) {
         print "Error: '.' is not a working copy\n";
         exit(1);
      }

      run("svn add -Nq log tmp public public/javascripts public/stylesheets");

      status('ignore', 'log/*');
      run("svn propset svn:ignore '*' log");

      status('ignore', 'tmp/*');
      run("svn propset svn:ignore '*' tmp");

      status('ignore', 'public/javascripts/all.*');
      status('ignore', 'public/stylesheets/all.*');
      run("svn propset svn:ignore 'all.*' public/javascripts public/stylesheets");
   }

   if ($add_externals and !is_dir('lib/.svn')) {
      status('externals', 'lib');
      rename('lib', 'lib.old');
      if (run("svn propset svn:externals %s .", "lib	http://dev.diarrhea.ch/svn/php-on-crutches/trunk/lib")) {
         rename('lib.old', 'lib');
      }

      if (term_exec("svn commit") and run("svn update") and is_dir('lib/.svn')) {
         rm_rf('lib.old');
      }
   }

?>
