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

   require LIB.'script/doc/generator.php';

   $paths = array();

   $title = null;
   $target = WEBROOT.'doc';
   $excludes = array();
   $force = false;
   $verbose = false;

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-t':
            $title = array_shift_arg($args);
            break;
         case '-d':
            $target = array_shift_arg($args);
            break;
         case '-e':
            if ($arg = realpath(array_shift_arg($args))) {
               $excludes[] = $arg;
            }
            break;
         case '-f':
            $force = true;
            break;
         case '-v':
            $verbose = true;
            break;
         default:
            if ($arg[0] == '-') {
               print "Usage: {$GLOBALS['argv'][0]} [OPTIONS] PATH\n"
                  . "\n"
                  . "  -t TITLE  Set the main title\n"
                  . "  -d PATH   Output directory for the documentation (default: WEBROOT/doc)\n"
                  . "  -e PATH   Exclude a path, can be passed multiple times\n"
                  . "  -f        Force overwriting an existing directory\n"
                  . "  -v        Show debug messages\n"
                  . "\n";
               exit(255);
            } else {
               if (file_exists($arg)) {
                  $paths[] = realpath($arg);
               } else {
                  print "$arg: No such file or directory";
                  exit(1);
               }
            }
      }
   }

   $doc = new DocGenerator(any($paths, APP), $excludes, $title);
   $doc->generate($target, $force, $verbose);

?>
