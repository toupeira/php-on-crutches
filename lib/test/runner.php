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

   safe_require(TEST.'test_helper.php');

   # Define TESTING for some custom behaviour when testing:
   # - config_init() always loads memory cache store
   # - Connection#set_headers() ignores header errors
   # - DatabaseConnection::load() appends '_test' to database configuration names
   # - Mail#send doesn't send out mails but stores them in $_SENT_MAILS instead
   define('TESTING', 1);

   # Reset some configuration values
   config_set('debug', false);
   config_set('debug_redirects', false);
   config_set('rewrite_urls', true);

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
      $models = array();

      foreach (glob(TEST.'fixtures/*.php') as $fixture) {
         $fixtures = null;
         include $fixture;
         if (!is_array($fixtures)) {
            throw new ConfigurationError("'$fixture' doesn't contain any fixtures");
         }

         # Get the model from the filename, strip extension and leading numbers
         preg_match('/^(\d+_)?(.+)\.php$/', basename($fixture), $match);
         $model = classify($match[2]);

         if (is_subclass_of($model, ActiveRecord)) {
            $models[$model] = $fixtures;
         } else {
            throw new ConfigurationError("'$model' is not an ActiveRecord model");
         }
      }

      # Store fixtures globally for access from fixture()
      $GLOBALS['_FIXTURES'] = &$models;

      # Empty tables in reverse order (to avoid foreign key conflicts)
      foreach (array_reverse($models) as $model => $fixtures) {
         DB($model)->delete_all();
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
         # Load database fixtures
         load_fixtures();

         # Reset sent mails
         $GLOBALS['_SENT_MAILS'] = null;

         return parent::invoke($method);
      }
   }

?>
