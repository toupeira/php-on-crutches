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
      if (is_file($path)) {
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

   function generate_model($name, $table=null) {
      if (preg_match('/^\w+$/', $name)) {
         $class = camelize($name);

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
         print "Usage: {$argv[0]} model NAME [TABLE]\n";
         exit(1);
      }
   }

   function generate_controller($name) {
      if (preg_match('/^\w+$/i', $name)) {
         $name = strtolower($name);
         $class = camelize($name);

         create_file(CONTROLLERS.underscore($name).'_controller.php', array(
            "class {$class}Controller extends ApplicationController",
            "{",
            "   function index() {",
            "   }",
            "}",
         ));
         create_file(TEST.'controllers/'.underscore($name).'_controller_test.php', array(
            "class {$class}ControllerTest extends ControllerTestCase",
            "{",
            "}",
         ));
         create_file(HELPERS.underscore($name).'_helper.php');
         create_file(TEST.'helpers/'.underscore($name).'_helper_test.php', array(
            "class {$class}HelperTest extends TestCase",
            "{",
            "}",
         ));
         create_directory(VIEWS.$name);
      } else {
         print "Usage: {$argv[0]} controller NAME\n";
         exit(1);
      }
   }

   $generator = "generate_{$argv[1]}";
   if (function_exists($generator)) {
      $generator($argv[2], $argv[3]);
   } else {
      print "Usage: {$argv[0]} [controller|model] NAME\n";
      exit(1);
   }

?>
