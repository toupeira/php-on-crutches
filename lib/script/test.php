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

   $_LOGGER->level = LOG_DISABLED;

   $paths = find_tests(array_slice($argv, 1));

   if ($paths === false) {
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
      foreach ($_TEST_DIRS as $test) {
         printf("  %-13s Run %s tests\n", $test, rtrim($test, 's'));
      }
      print "\n";
      exit(255);

   } elseif (empty($paths)) {
      print "Nothing to test.\n";
      exit(1);

   } else {
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
   }

?>
