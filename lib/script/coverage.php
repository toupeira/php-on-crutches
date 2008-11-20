#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   $_ENV['ENVIRONMENT'] = 'test';

   if (function_exists('xdebug_start_code_coverage')) {
      xdebug_start_code_coverage();
   } else {
      print "You need Xdebug to collect code coverage.\n";
      exit(1);
   }

   require_once dirname(__FILE__).'/../script.php';
   require_once LIB.'test/runner.php';
   require_once LIB.'test/coverage.php';

   $tests = array();

   $force = false;
   $target = WEBROOT.'coverage';
   $include = array(APP);
   $exclude = array();

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch($arg) {
         case '-f': $force = true; break;
         case '-d': $target = realpath(array_shift($args)); break;
         case '-i': $include[] = realpath(array_shift($args)); break;
         case '-e': $exclude[] = realpath(array_shift($args)); break;
         default: $tests[] = $arg;
      }
   }

   # Reset the arguments for script/test
   array_unshift($tests, $argv[0]);
   $argv = $tests;

   # Run the tests and collect coverage data
   require LIB.'script/test.php';
   $coverage = xdebug_get_code_coverage();
   xdebug_stop_code_coverage();

   # Include libraries in report if a framework test was selected
   foreach ($paths as $path) {
      if (strpos($path, 'test/framework') !== false) {
         $include[] = LIB;
         break;
      }
   }

   $report = new CoverageReport($coverage, $target, $include, $exclude);
   $report->generate($force) or exit(1);

   # Open the report in a browser
   if (getenv('DISPLAY')) {
      system("x-www-browser $target/index.html &>/dev/null &");
   }

?>
