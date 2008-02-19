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
   require_once LIB.'test/runner.php';

   $logger->level = LOG_DISABLED;

   $tests = glob(TEST.'*');
   $tests = array_map(basename, array_filter($tests, is_dir));
   array_remove($tests, array('coverage', 'fixtures', 'framework'));

   $framework = TEST.'framework';
   is_dir($framework) or $framework = null;

   $paths = array();
   $args = array_slice($argv, 1);
   if (empty($args)) {
      $paths = $tests;
   }

   while ($arg = array_shift($args)) {
      switch ($arg) {
         case 'all':
            $paths = array_merge($paths, $tests);
            if ($framework) {
               array_unshift($paths, $framework);
            }
            break;
         case 'app':
            $paths = array_merge($paths, $tests);
            break;
         case 'framework':
            if ($framework) {
               $paths[] = $framework;
            }
            break;
         case 'recent':
            $paths = array_merge($paths, find_tests(
               "find ".ROOT." -name .svn -prune -o -type f -name '*.php' -mmin -10 -print"));
            break;
         case 'uncommitted':
            $paths = array_merge($paths, find_tests(
               "svn stat ".ROOT." | grep '^M ' | cut -dM -f2-"));
            break;
         default:
            if (in_array($arg, $tests) or file_exists($arg)) {
               $paths[] = $arg;
            } else {
               if (strstr($argv[0], 'coverage')) {
                  print "Usage: $argv[0] [OPTIONS] [TESTS..]\n"
                        . "\n"
                        . "  -d DIR        Output directory for the report\n"
                        . "  -i PATH       Include path in report\n"
                        . "  -e PATH       Exclude path in report\n"
                        . "  -f            Overwrite an existing directory\n";
               } else {
                  print "Usage: $argv[0] [TESTS..]\n";
               }
               print "\n"
                     . "Tests:\n"
                     . "  all           Run all tests\n"
                     . "  app           Run application tests (default)\n"
       . ($framework ? "  framework     Run framework tests\n" : "")
                     . "\n"
                     . "Special tests:\n"
                     . "  recent        Test recently changed files\n"
                     . "  uncommitted   Test uncommitted files\n"
                     . "\n"
                     . "Available tests:\n";
               foreach ($tests as $test) {
                  printf("  %-13s Run %s tests\n", $test, rtrim($test, 's'));
               }
               print "\n";
               exit(255);
            }
      }
   }

   if (empty($paths)) {
      print "Nothing to test.\n";
      exit(1);
   }

   print "\n";
   $errors = array();
   foreach (array_unique((array) $paths) as $path) {
      run_tests($path) or $errors[] = $path;
   }

   if ($errors) {
      print "Encountered errors in the following tests:\n";
      foreach ($errors as $error) {
         print "  $error\n";
      }
      print "\n";
      exit(1);
   }

?>
