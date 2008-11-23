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
      printf("%12s  %s\n", $action, str_replace(ROOT, '', $path));
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
            status('error', $path);
            print "\n";
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

   function edit_file($path, $pattern, $replacement) {
      if (is_file($path)) {
         status('edit', $path);
         run('sed -ri %s %s', "s/$pattern/$replacement/", $path);
      } else {
         status('error', $path);
         print "\n";
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

   function generate_controller($name, $parent='ApplicationController') {
      $name = strtolower($name);
      check_class($class = camelize($name).'Controller');

      create_file(CONTROLLERS.underscore($name).'_controller.php', array(
         "class {$class} extends $parent",
         "{",
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

      return true;
   }

   function generate_model($name=null, $parent='Model', $mapper='ModelMapper') {
      check_class($class = camelize($name));

      create_file(MODELS.underscore($name).'.php', array(
         "class {$class}Mapper extends $mapper",
         "{",
         "}",
         "",
         "class {$class} extends $parent",
         "{",
         "}",
      ));

      create_file(FIXTURES.'00_'.underscore($name).'.php', array(
         "\$fixtures = array(",
         ");",
      ));

      create_file(TEST.'models/'.underscore($name).'_test.php', array(
         "class {$class}Test extends ModelTestCase",
         "{",
         "}",
      ));

      return true;
   }

   function generate_db_model($name, $parent=ActiveRecord, $mapper=DatabaseMapper) {
      return generate_model($name, $parent, $mapper);
   }

   function generate_authentication($model_name, $controller_name) {
      generate_db_model($model_name, AuthenticationModel);
      generate_controller($controller_name, AuthenticationController);

      if (!$model = classify($model_name) or !is_subclass_of($model, Model)) {
         status('error', "Invalid model '$model_name'");
         print "\n";
         return;
      }

      if (!$controller = classify($controller_name.'Controller') or !is_subclass_of($controller, Controller)) {
         status('error', "Invalid controller '$controller_name'");
         print "\n";
         return;
      }

      edit_file(CONTROLLERS.'application_controller.php', "^( *abstract class .* extends) Controller$", "\\1 AuthenticatedController");
      edit_file(CONFIG.'application.php', "^( *'auth_model' *=>).*", "\\1 $model,");
      edit_file(CONFIG.'application.php', "^( *'auth_controller' *=>).*", "\\1 $controller,");

      return true;
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
      try {
         if (call_user_func_array($generator, $args)) {
            exit;
         } else {
            exit(1);
         }
      } catch (Exception $e) {}
   }

   print "Usage: {$argv[0]} [-f] GENERATOR OPTIONS\n"
         . "\n"
         . "Available generators:\n"
         . "\n"
         . "  controller NAME [PARENT]\n"
         . "    Generate a controller, including views folder and tests\n"
         . "\n"
         . "  model NAME [PARENT] [MAPPER]\n"
         . "    Generate a model, including mapper and tests\n"
         . "\n"
         . "  db_model NAME [PARENT] [MAPPER]\n"
         . "    Generate a database model, including mapper and tests\n"
         . "\n"
         . "  authentication MODEL CONTROLLER\n"
         . "    Enable the authentication system and generate scaffold classes\n"
         . "\n";

?>
