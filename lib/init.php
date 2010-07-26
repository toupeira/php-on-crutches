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

   # Load core libraries
   require LIB.'core/init.php';

   # Set the current environment
   define_default('ENVIRONMENT', any(
      $_SERVER['ENVIRONMENT'],
      $_ENV['ENVIRONMENT'],
      'development'
   ));

   # Load framework libraries
   require LIB.'errors.php';
   require LIB.'logger.php';
   require LIB.'cache.php';
   require LIB.'session.php';
   require LIB.'config.php';

   require LIB.'dispatcher.php';
   require LIB.'router.php';
   require LIB.'controller.php';
   require LIB.'model.php';
   require LIB.'mapper.php';
   require LIB.'view.php';
   require LIB.'mail.php';

   # Initialize the application
   foreach (glob(CONFIG.'initializers/*.php') as $initializer) {
      require $initializer;
   }

   # Load framework helpers
   foreach (glob(LIB.'helpers/*.php') as $helper) {
      require $helper;
   }

   # Initialize the framework
   config_init();

   try_require(CONTROLLERS.'application_controller.php');
   try_require(HELPERS.'application_helper.php');

?>
