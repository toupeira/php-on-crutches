<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Load standard libraries
   $libs = array(
      'base',
      'logger',
      'config',

      'dispatcher',
      'controller',
      'model',
      'view',
   );

   foreach ($libs as $lib) {
      require LIB."$lib.php";
   }

   # Load standard helpers
   $helpers = glob(LIB.'helpers/*.php');
   foreach ($helpers as $helper) {
      require $helper;
   }

   # Load application helper
   @include_once HELPERS.'application_helper.php';

   # Load route definitions
   @include CONFIG.'routes.php';

   # Load database definitions
   @include CONFIG.'database.php';

   # Load database support if necessary
   if (!empty($_DATABASE)) {
      require LIB.'database/base.php';
   }

   # Initialize the framework
   Logger::init();
   Dispatcher::init();

?>
