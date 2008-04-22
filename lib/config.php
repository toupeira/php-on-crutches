<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require CONFIG.'framework.php';
   require CONFIG.'application.php';
   require CONFIG.'routes.php';
   require CONFIG.'database.php';

   $_CONFIG = array_merge($_FRAMEWORK, (array) $_APPLICATION);

   function config($key) {
      return $GLOBALS['_CONFIG'][$key];
   }

   function config_set($key, $value) {
      return $GLOBALS['_CONFIG'][$key] = $value;
   }

   function config_init() {
      $config = &$GLOBALS['_CONFIG'];

      # Start output buffering (unless running in a console)
      if (PHP_SAPI != 'cli') {
         ob_start();
      }

      # Configure error reporting
      ini_set('display_errors', (true or $config['debug'] or PHP_SAPI == 'cli'));
      error_reporting(E_ALL ^ E_NOTICE);
      set_error_handler('error_handler', error_reporting());
      (PHP_SAPI != 'cli') and set_exception_handler('exception_handler');

      # Load routes
      if (!empty($GLOBALS['_ROUTES'])) {
         Router::add($GLOBALS['_ROUTES']);
      }

      # Load database support if necessary
      if (!empty($GLOBALS['_DATABASE'])) {
         require LIB.'database/base.php';
      }

      # Configure the logger
      $log_file = (PHP_SAPI == 'cli')
         ? STDERR
         : any($config['log_file'], LOG.'application.log');
      $GLOBALS['_LOGGER'] = new Logger($log_file, any($config['log_level'], LOG_INFO));

      # Setup cache store, always use memory store for debug mode and testing
      if ($config['debug'] or defined('TESTING')) {
         $store = 'memory';
      } else {
         $store = $config['cache_store'];
      }
      load_store('cache', $store, 'memory');

      # Setup session store if enabled and not running in a console
      if ($store = $config['session_store'] and PHP_SAPI != 'cli') {
         if ($store != 'php' and $store = load_store('session', $store, 'cookie')) {
            session_set_save_handler(
               array($store, 'open'),
               array($store, 'close'),
               array($store, 'read'),
               array($store, 'write'),
               array($store, 'destroy'),
               array($store, 'expire')
            );
         }

         session_start();
         header('Cache-Control: private');
         header('Pragma: cache');

         register_shutdown_function(session_write_close);
      }

      # Setup mbstring
      mb_internal_encoding('UTF-8');

      # Configure gettext domain
      textdomain($config['application']);
      bindtextdomain($config['application'], LANG);
      bind_textdomain_codeset($config['application'], 'UTF-8');

      # Set a locale, so $LANGUAGE will be respected
      if (!setlocale(LC_MESSAGES, 'en_US.UTF-8')) {
         log_warn("Couldn't load UTF-8 locale");
      }

      # Set C as global locale, to avoid subprocesses inheriting our locale
      putenv("LANG=C");

      # Set the default language if set
      if ($lang = $config['languages'][0]) {
         set_language($lang);
      }

      # Work around magic quotes...
      if (get_magic_quotes_gpc()) {
         fix_magic_quotes();
      }
   }

   # Change the current language used for gettext and templates
   function set_language($lang) {
      if (in_array($lang, config('languages'))) {
         config_set('language', $lang);
         # Using $LANGUAGE allows arbitrary locale names
         putenv("LANGUAGE=$lang");
      } else {
         log_warn("Invalid language '$lang'");
      }
   }

   # Helper for loading the cache and session stores
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
         throw new ConfigurationError("Invalid $type store '$class'");
      }
   }

?>
