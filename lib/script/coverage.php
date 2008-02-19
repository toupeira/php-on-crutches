#!/usr/bin/php5
<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   if (function_exists('xdebug_start_code_coverage')) {
      xdebug_start_code_coverage();
   } else {
      print "You need Xdebug to collect code coverage.\n";
      exit(1);
   }

   require_once dirname(__FILE__).'/../../config/environment.php';
   require_once LIB.'test/runner.php';
   require_once LIB.'test/coverage.php';

   $logger->level = LOG_DISABLED;

   $tests = array();
   $include = array(APP);
   $exclude = array();

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch($arg) {
         case '-d': $target = realpath(array_shift($args)); break;
         case '-i': $include[] = realpath(array_shift($args)); break;
         case '-e': $exclude[] = realpath(array_shift($args)); break;
         case '-f': $force = true; break;
         default: $tests[] = $arg;
      }
   }

   $target = any($target, WEBROOT.'coverage');
   if (file_exists($target) and !$force) {
      print "Target path $target already exists.\n";
      exit(1);
   }

   # Reset the arguments for script/test
   array_unshift($tests, $argv[0]);
   $argv = $tests;

   # Run the tests and collect coverage data
   require SCRIPT.'test.php';
   $coverage = xdebug_get_code_coverage();
   xdebug_stop_code_coverage();

   # Include libraries in report if a framework test was selected
   foreach ($paths as $path) {
      if (strpos($path, 'test/framework') !== false) {
         $include[] = LIB;
         break;
      }
   }

   rm_rf($target);
   $report = new CoverageReport($coverage, $target, $include, $exclude);
   $report->generate() or exit(1);

   # Open the report in a browser
   if (getenv('DISPLAY')) {
      system("x-www-browser $target/index.html &>/dev/null &");
   }

?>
