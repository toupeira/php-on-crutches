<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Always display errors until the framework is initialized
   ini_set('display_errors', true);

   # Load utility libraries
   foreach (glob(LIB.'util/*.php') as $util) {
      require $util;
   }

   # Set the current environment
   define_default('ENVIRONMENT', any($_SERVER['ENVIRONMENT'], 'development'));

   # Load framework libraries
   require LIB.'errors.php';
   require LIB.'logger.php';
   require LIB.'cache.php';
   require LIB.'session.php';
   require LIB.'config.php';

   require LIB.'router.php';
   require LIB.'dispatcher.php';
   require LIB.'controller.php';
   require LIB.'model.php';
   require LIB.'view.php';
   require LIB.'mail.php';

   # Initialize the framework
   config_init();

   # Auto-load models and controllers
   function __autoload($class) {
      $name = underscore($class);
      if (is_file($file = MODELS."$name.php")) {
         return require $file;
      } elseif (substr($name, -6) == 'mapper' and
         is_file($file = MODELS.substr($name, 0, -7).'.php')) {
         return require $file;
      } elseif (substr($name, -10) == 'controller') {
         if (is_file($file = CONTROLLERS."$name.php")) {
            return require $file;
         } elseif (is_file($file = LIB."controllers/$name.php")) {
            return require $file;
         }
      }
   }

   # Initialize the application
   foreach (glob(CONFIG.'initializers/*.php') as $initializer) {
      require $initializer;
   }

   # Load framework helpers
   foreach (glob(LIB.'helpers/*.php') as $helper) {
      require $helper;
   }

   require CONTROLLERS.'application_controller.php';
   try_require(HELPERS.'application_helper.php');

?>
