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

   function status($action, $path) {
      printf("%12s  %s\n", $action, substr($path, strlen(ROOT)));
   }

   function create_file($path, $lines=null) {
      if (is_file($path) and !$GLOBALS['force']) {
         status('exists', $path);
      } else {
         status('create', $path);

         $content = "<?\n\n";
         foreach ((array) $lines as $line) {
            $content .= "   $line\n";
         }
         $content .= "\n?>\n";

         if (file_put_contents($path, $content) === false) {
            print "  error\n";
         }
      }
   }

   function create_directory($path) {
      if (is_dir($path)) {
         status('exists', $path);
      } else {
         status('create', $path);
         if (!mkdir($path)) {
            status('error', $path);
            print "\n";
         }
      }
   }

   function check_class($class) {
      if (classify($class) and !$GLOBALS['force']) {
         print "Class $class already exists, use -f to force.\n";
         exit(1);
      } elseif (preg_match('/^\w+$/', $class)) {
         return true;
      }
   }

   function generate_model($name, $table=null) {
      if (check_class($class = camelize($name))) {
         if (preg_match('/^\w+$/', $table)) {
            create_file(MODELS.underscore($name).'.php', array(
               "class {$class} extends ActiveRecord",
               "{",
               "   protected \$table = '$table';",
               "}",
            ));
            create_file(FIXTURES.'00_'.underscore($name).'.php', array(
               "\$fixtures = array(",
               ");",
            ));
         } else {
            create_file(MODELS.underscore($name).'.php', array(
               "class {$class} extends Model",
               "{",
               "}",
            ));
         }

         create_file(TEST.'models/'.underscore($name).'_test.php', array(
            "class {$class}Test extends ModelTestCase",
            "{",
            "}",
         ));
      } else {
         print "Usage: {$argv[0]} [-f] model NAME [TABLE]\n";
         exit(1);
      }
   }

   function generate_controller($name) {
      $name = strtolower($name);
      $class = camelize($name).'Controller';
      if (check_class($class)) {
         create_file(CONTROLLERS.underscore($name).'_controller.php', array(
            "class {$class} extends ApplicationController",
            "{",
            "   function index() {",
            "   }",
            "}",
         ));
         create_file(TEST.'controllers/'.underscore($name).'_controller_test.php', array(
            "class {$class}Test extends ControllerTestCase",
            "{",
            "}",
         ));
         create_file(HELPERS.underscore($name).'_helper.php');
         create_file(TEST.'helpers/'.underscore($name).'_helper_test.php', array(
            "class ".camelize($name)."HelperTest extends TestCase",
            "{",
            "}",
         ));
         create_directory(VIEWS.$name);
      } else {
         print "Usage: {$argv[0]} [-f] controller NAME\n";
         exit(1);
      }
   }

   $args = array_slice($argv, 1);

   if ($args[0] == '-f') {
      $force = true;
      array_shift($args);
   } else {
      $force = false;
   }

   $generator = 'generate_'.array_shift($args);
   if (function_exists($generator)) {
      call_user_func_array($generator, $args);
   } else {
      print "Usage: {$argv[0]} [-f] [controller|model] NAME\n";
      exit(1);
   }

?>
