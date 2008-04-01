<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Load core extensions
   foreach (glob(LIB.'core/*.php') as $core) {
      require $core;
   }

   # Load framework libraries
   require LIB.'config.php';
   require LIB.'errors.php';
   require LIB.'cache.php';
   require LIB.'session.php';

   require LIB.'router.php';
   require LIB.'dispatcher.php';
   require LIB.'controller.php';

   require LIB.'model.php';
   require LIB.'view.php';

   foreach (glob(LIB.'helpers/*.php') as $helper) {
      require $helper;
   }

   # Initialize the framework
   config_init();

   # Auto-load models and controllers
   function __autoload($class) {
      $name = underscore($class);
      if (is_file($file = MODELS."$name.php")) {
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

   @require CONTROLLERS.'application_controller.php';
   @include HELPERS.'application_helper.php';

?>
