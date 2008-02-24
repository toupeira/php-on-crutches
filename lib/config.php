<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require CONFIG.'framework.php';

   @include CONFIG.'application.php';
   @include CONFIG.'routes.php';
   @include CONFIG.'database.php';

   $_CONFIG = array_merge($_FRAMEWORK, (array) $_APPLICATION);

   function config($key) {
      return $GLOBALS['_CONFIG'][$key];
   }

   function config_set($key, $value) {
      $GLOBALS['_CONFIG'][$key] = $value;
   }

   function config_init() {
      $config = &$GLOBALS['_CONFIG'];

      # Configure error reporting
      if ($config['debug'] or PHP_SAPI == 'cli') {
         error_reporting(E_ALL ^ E_NOTICE);
         ini_set('display_errors', 1);
      } else {
         error_reporting(0);
         ini_set('display_errors', 0);
      }

      # Configure the logger
      $log_file = (PHP_SAPI == 'cli')
         ? STDERR
         : $log_file = any($config['log_file'], LOG.'application.log');
      log_init($log_file, any($config['log_level'], LOG_INFO));

      # Start sessions if enabled and not running in a console
      if ($config['use_sessions'] and PHP_SAPI != 'cli') {
         session_start();
         header('Cache-Control: private');
         header('Pragma: cache');
      }

      # Work around magic quotes...
      if (get_magic_quotes_gpc()) {
         log_info("Reverting magic quotes... (DISABLE IT ALREADY!!)");
         fix_magic_quotes();
      }
   }

?>
