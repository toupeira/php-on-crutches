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

   require_once dirname(__FILE__).'/../script.php';
   require_once LIB.'script/test/runner.php';

   if (in_array('-v', $argv)) {
      array_remove($argv, '-v');
      log_level_set(LOG_DEBUG);
   } else {
      log_level_set(LOG_DISABLED);
   }

   $paths = find_tests(array_slice($argv, 1));

   if ($paths === false) {
      if (strstr($argv[0], 'coverage')) {
         print "Usage: $argv[0] [OPTIONS] [TESTS..]\n"
               . "\n"
               . "  -d PATH       Output directory for the report (default: WEBROOT/coverage)\n"
               . "  -i PATH       Include path in report\n"
               . "  -e PATH       Exclude path in report\n"
               . "  -f            Force overwriting an existing directory\n";
      } else {
         print "Usage: $argv[0] [TESTS..]\n";
      }

      print "\n"
            . "Tests:\n"
            . "  all           Run all tests\n"
            . "  app           Run application tests (default)\n"
            . "  framework     Run framework tests\n"
            . "\n"
            . "Special tests:\n"
            . "  recent        Test recently changed files\n"
            . "  uncommitted   Test uncommitted files\n"
            . "\n"
            . "Available tests:\n";
      foreach ($_TEST_DIRS as $test) {
         printf("  %-13s Run %s tests\n", $test, singularize($test));
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
