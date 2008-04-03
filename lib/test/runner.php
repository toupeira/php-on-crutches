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

   # Used for some custom behaviour when testing:
   # - config_init() always loads memory cache store
   # - DatabaseConnection::load() appends '_test' to database configuration names
   define('TESTING', 1);

   global $_TEST_DIRS;
   $_TEST_DIRS = array_map(basename, array_filter(glob(TEST.'*'), is_dir));
   array_remove($_TEST_DIRS, array('coverage', 'fixtures', 'framework'));

   function run_tests($path, $message=null, $reporter=null) {
      $group = new GroupTest($message);
      $name = basename($path);

      if (is_file($path)) {
         $message = "Testing ".basename($path).": ";
         $group->addTestFile($path);
      } else {
         $message = "Testing $name: ";
         $dir = TEST.$name;
         foreach (find_files($dir, '-type f -name "*_test.php"') as $file) {
            $group->addTestFile($file);
         }
      }

      print $message.pluralize($group->getSize(), 'test', 'tests');
      if ($group->getSize() > 0) {
         if (!$reporter) {
            $reporter = new ConsoleReporter();
         }

         try {
            $group->run($reporter);
         } catch (Exception $e) {
            print "\nCaught $e\n";
         }

         print "\n";
         return $reporter->getStatus();
      } else {
         print "\n\n";
         return true;
      }
   }

   function find_tests($args) {
      global $_TEST_DIRS;

      $args = (array) $args;
      $paths = (empty($args) ? $_TEST_DIRS : array());
      $framework = (is_dir($framework = TEST.'framework') ? $framework : null);

      while ($arg = array_shift($args)) {
         switch ($arg) {
            case 'all':
               $paths = array_merge($paths, $_TEST_DIRS);
               if ($framework) {
                  array_unshift($paths, $framework);
               }
               break;
            case 'app':
               $paths = array_merge($paths, $_TEST_DIRS);
               break;
            case 'framework':
               if ($framework) {
                  $paths[] = $framework;
               }
               break;
            case 'recent':
               $paths = array_merge($paths, find_related_tests(
                  find_files(ROOT, '-name .svn -prune -o -type f -name "*.php" -mmin -10 -print')));
               break;
            case 'uncommitted':
               $paths = array_merge($paths, find_related_tests(
                  "svn stat ".ROOT." | grep '^M ' | cut -dM -f2-"));
               break;
            default:
               if (in_array($arg, $_TEST_DIRS) or file_exists($arg)) {
                  $paths[] = $arg;
               } else {
                  return false;
               }
               break;
         }
      }

      return array_unique($paths);
   }

   function find_related_tests($files) {
      $tests = array();

      foreach ($files as $file) {
         $name = str_replace('.php', '', basename($file));
         if ($test = find_files(TEST, "-type f -name '{$name}_test.php' | head -1")) {
            $tests[] = trim($test[0]);
         }
      }

      return $tests;
   }

   function load_fixtures() {
      foreach (glob(TEST.'fixtures/*.php') as $fixture) {
         $fixtures = null;
         include $fixture;
         if (!is_array($fixtures)) {
            throw new ConfigurationError("'$fixture' doesn't contain any fixtures");
         }

         $model = classify(substr(basename($fixture), 0, -4));
         if (!is_subclass_of($model, ActiveRecord)) {
            throw new ConfigurationError("'$model' is not an ActiveRecord model");
         }

         DB($model)->delete_all();
         foreach ($fixtures as $fixture) {
            DB($model)->create($fixture);
         }
      }
   }

   class ConsoleReporter extends TextReporter
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

   class CustomInvoker extends SimpleInvoker
   {
      function invoke($method) {
         load_fixtures();
         return parent::invoke($method);
      }
   }

?>
