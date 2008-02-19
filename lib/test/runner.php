<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require 'simpletest/unit_tester.php';
   require 'simpletest/reporter.php';
   require LIB.'test/cases.php';

   @include TEST.'test_helper.php';

   function run_tests($path, $message=null, $reporter=null) {
      $group = new GroupTest($message);
      $name = basename($path);

      if (is_file($path)) {
         $message = "Testing ".basename($path).": ";
         $group->addTestFile($path);
      } else {
         $message = "Testing $name: ";
         $dir = TEST.$name;
         foreach (explode("\n", `find "$dir" -type f -name '*_test.php'`) as $file) {
            if (is_file($file)) {
               $group->addTestFile($file);
            }
         }
      }

      print $message.pluralize($group->getSize(), 'test', 'tests');
      if ($group->getSize() > 0) {
         $reporter = any($reporter, new Reporter());
         $group->run($reporter);
         print "\n";
         return $reporter->getStatus();
      } else {
         print "\n\n";
         return true;
      }
   }

   function find_tests($command) {
      $tests = array();
      $files = explode("\n", shell_exec($command));
      if ($files == array('')) {
         return $tests;
      }

      foreach ($files as $file) {
         $name = str_replace('.php', '', basename($file));
         $test = shell_exec("find ".TEST." -type f -name '{$name}_test.php' | head -1");
         if ($test) {
            $tests[] = trim($test);
         }
      }
      return $tests;
   }

   class Reporter extends TextReporter
   {
      function paintPass($message) {
         print ".";
         parent::paintPass($message);
      }

      function paintFail($message) {
         print "F\n";
         parent::paintFail($message);
      }

      function paintError($message) {
         print "E\n";
         parent::paintError($message);
      }
   }

?>
