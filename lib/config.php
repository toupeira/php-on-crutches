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
      ini_set('display_errors', ($config['debug'] or PHP_SAPI == 'cli'));
      error_reporting(E_ALL ^ E_NOTICE);
      set_error_handler('error_handler', error_reporting());
      (PHP_SAPI != 'cli') and set_exception_handler('exception_handler');

      # Load routes
      if (!empty($GLOBALS['_ROUTES'])) {
         Router::add($GLOBALS['_ROUTES']);
      }

      # Load databases
      if (!empty($GLOBALS['_DATABASE'])) {
         require LIB.'database/base.php';
      }

      # Configure the logger
      $log_file = (PHP_SAPI == 'cli')
         ? STDERR
         : any($config['log_file'], LOG.'application.log');
      log_init($log_file, any($config['log_level'], LOG_INFO));

      # Setup session store if enabled and not running in a console
      if ($store = $config['session_store'] and PHP_SAPI != 'cli') {
         if ($store != 'php' and $store = load_store('session', $store, 'cookie')) {
            session_set_save_handler(
               array($store, 'open'),
               array($store, 'close'),
               array($store, 'read'),
               array($store, 'write'),
               array($store, 'delete'),
               array($store, 'expire')
            );
         }

         session_start();
         header('Cache-Control: private');
         header('Pragma: cache');
      }

      # Setup cache store
      load_store('cache', $config['cache_store'], 'memory');

      # Work around magic quotes...
      if (get_magic_quotes_gpc()) {
         log_info("Reverting magic quotes... (DISABLE IT ALREADY!!)");
         fix_magic_quotes();
      }
   }

   function load_store($type, $store, $default=null) {
      if (!$store) {
         if ($default) {
            $store = $default;
         } else {
            return;
         }
      }

      $base = ucfirst($type).'Store';
      $class = $base.ucfirst($store);
      $default = $base.ucfirst($default);

      if (class_exists($class) and $store = new $class() and $store instanceof $base) {
         if (!$store->setup() and $class != $default) {
            log_warn("Couldn't load $type store '$class', using '$default'");
            $store = new $default();
         }

         return $GLOBALS['_'.strtoupper($type).'_STORE'] = $store;
      } else {
         raise("Invalid $type store '$class'");
      }
   }

?>
