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
   require LIB.'cache.php';

   require LIB.'route.php';
   require LIB.'dispatcher.php';
   require LIB.'controller.php';

   require LIB.'model.php';
   require LIB.'view.php';

   # Load helpers
   @include_once HELPERS.'application_helper.php';
   foreach (glob(LIB.'helpers/*.php') as $helper) {
      require $helper;
   }

   # Initialize the framework
   config_init();

   # Auto-load models and controllers
   function __autoload($class) {
      $class = underscore($class);
      if (is_file($file = MODELS."$class.php")) {
         require $file;
      } elseif (substr($class, -10) == 'controller') {
         if (is_file($file = CONTROLLERS."$class.php")) {
            require $file;
         } elseif (is_file($file = LIB."controllers/$class.php")) {
            require $file;
         }
      }
   }

   # Initialize the application
   foreach (glob(CONFIG.'initializers/*.php') as $initializer) {
      require $initializer;
   }

?>
