#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../script.php';

   function status($action, $path) {
      printf("%12s  %s\n", $action, substr($path, strlen(ROOT)));
   }

   function create_file($path, array $lines=null) {
      if (is_file($path) and !$GLOBALS['force']) {
         status('exists', $path);
      } else {
         status('create', $path);

         $content = "<?# \$Id\$ ?>\n<?\n\n";
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

   function generate_model($name=null, $table=null) {
      if ($name and check_class($class = camelize($name))) {
         if (preg_match('/^\w+$/', $table)) {
            create_file(MODELS.underscore($name).'.php', array(
               "class {$class}Mapper extends DatabaseMapper",
               "{",
               "   protected \$_table = '$table';",
               "}",
               "",
               "class {$class} extends ActiveRecord",
               "{",
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
         print "Usage: {$GLOBALS['argv'][0]} [-f] model NAME [TABLE]\n";
         exit(1);
      }
   }

   function generate_controller($name=null) {
      $name = strtolower($name);
      $class = camelize($name).'Controller';
      if ($name and check_class($class)) {
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
      print "Usage: {$argv[0]} [-f] controller NAME\n"
          . str_repeat(' ', strlen($argv[0]))
          . "             model NAME [TABLE]\n";
      exit(1);
   }

?>
