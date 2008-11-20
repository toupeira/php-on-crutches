<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   if (ENVIRONMENT != 'test') {
      throw new ConfigurationError("Can only run tests in test environment");
   }

   require 'simpletest/unit_tester.php';
   require 'simpletest/reporter.php';

   require LIB.'test/cases.php';

   global $_TEST_DIRS;
   $_TEST_DIRS = array_map('basename', array_filter(glob(TEST.'*'), is_dir));
   array_remove($_TEST_DIRS, array('fixtures', 'framework'));

   function run_tests($path, $message=null, $reporter=null) {
      $group = new GroupTest($message);
      $name = basename($path);
      $dirs = array();

      if (is_file($path)) {
         $message = "Testing [1m".basename($path)."[0m: ";
         $dir = dirname(realpath($path));
         $dirs[] = $dir;
         load_helpers($dir);
         $group->addTestFile($path);
      } else {
         $message = "Testing [1m$name[0m: ";
         $dir = TEST.$name;
         foreach (find_files("$dir/", '-type f -name "*_test.php"') as $file) {
            load_helpers(dirname($file));
            $group->addTestFile($file);
            $dirs[] = dirname($file);
         }
      }

      print $message.pluralize($group->getSize(), 'test');
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
      $framework = TEST.'framework';

      while ($arg = array_shift($args)) {
         switch ($arg) {
            case 'all':
               $paths = array_merge($paths, $_TEST_DIRS);
               array_unshift($paths, $framework);
               break;
            case 'app':
               $paths = array_merge($paths, $_TEST_DIRS);
               break;
            case 'framework':
               $paths[] = $framework;
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

   function find_related_tests(array $files) {
      $tests = array();

      foreach ($files as $file) {
         $name = str_replace('.php', '', basename($file));
         if ($test = find_files(TEST, "-type f -name '{$name}_test.php' | head -1")) {
            $tests[] = trim($test[0]);
         }
      }

      return $tests;
   }

   # Load all test helpers for the given path
   function load_helpers($path) {
      if (substr(str_replace(ROOT, '', $path), 0, 14) == 'test/framework') {
         # Don't load application test helper for framework tests
         $root = LIB.'test/framework';
      } else {
         $root = TEST;
      }

      if (is_file($path)) {
         $path = dirname($path);
      }

      if ($dir = realpath($path)) {
         while (check_root($dir, $root)) {
            try_require($dir.'/test_helper.php');
            $dir = dirname($dir);
         }
      }
   }

   function load_fixtures() {
      $models = array();

      foreach (glob(TEST.'fixtures/*.php') as $fixture) {
         $fixtures = null;
         include $fixture;
         $fixture = basename($fixture);
         if (!is_array($fixtures)) {
            throw new ConfigurationError("Fixture '$fixture' doesn't contain any fixtures");
         }

         # Get the model from the filename, strip extension and leading numbers
         preg_match('/^(\d+_)?(.+)\.php$/', $fixture, $match);
         if (!$model = classify($match[2])) {
            throw new ConfigurationError("Fixture '$fixture' doesn't contain a valid class name");
         } elseif (!is_subclass_of($model, ActiveRecord)) {
            throw new ConfigurationError("'$model' is not an ActiveRecord model");
         } else {
            $models[$model] = $fixtures;
         }
      }

      # Store fixtures globally for access from fixture()
      $GLOBALS['_FIXTURES'] = &$models;

      # Empty tables in reverse order (to avoid foreign key conflicts)
      foreach (array_reverse($models) as $model => $fixtures) {
         DB($model)->delete_all(true);
      }

      # Fill tables in order
      foreach ($models as $model => $fixtures) {
         foreach ($fixtures as $name => $fixture) {
            DB($model)->insert($fixture);
         }
      }
   }

   function fixture($model, $key) {
      return $GLOBALS['_FIXTURES'][$model][$key];
   }

   class ConsoleReporter extends TextReporter
   {
      function paintPass($message) {
         print ".";
         parent::paintPass($message);
      }

      function paintFail($message) {
         while (ob_get_level()) {
            ob_end_clean();
         }

         print "F\n";
         parent::paintFail($message);
      }

      function paintError($message) {
         while (ob_get_level()) {
            ob_end_clean();
         }

         print "E\n";
         parent::paintError($message);
      }
   }

   class CustomInvoker extends SimpleInvoker
   {
      function invoke($method) {
         # Reset output buffering
         while (ob_get_level()) {
            ob_end_clean();
         }

         # Load database fixtures
         load_fixtures();

         # Reset sent mails
         $GLOBALS['_SENT_MAILS'] = null;

         method_exists($this, 'before') and $this->before($method);
         $result =  parent::invoke($method);
         method_exists($this, 'after') and $this->after($method);

         return $result;
      }
   }

?>
