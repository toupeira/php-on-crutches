<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require CONFIG.'application.php';

   if (is_file($config = CONFIG.'environments/'.ENVIRONMENT.'.php')) {
      require $config;
   }

   require CONFIG.'routes.php';
   require CONFIG.'database.php';

   # Default framework settings
   $_CONFIG['defaults'] = array(
      'name'              => basename(ROOT),
      'languages'         => null,

      'log_file'          => LOG.ENVIRONMENT.'.log',
      'log_level'         => LOG_INFO,

      'session_store'     => 'php',
      'cache_store'       => 'memory',
      'cache_path'        => TMP.'cache',
      'merge_assets'      => false,

      'error_handler'     => error_handler,
      'exception_handler' => exception_handler,

      'output_buffering'  => true,
      'rewrite_urls'      => true,

      'debug'             => false,
      'debug_redirects'   => false,
      'debug_queries'     => false,

      'send_mails'        => true,
      'mail_from'         => '',
      'mail_from_name'    => '',
      'notify_exceptions' => null,
      'trusted_hosts'     => array('127.0.0.1'),
   );

   # Merge application settings
   $_CONFIG['application'] = array_merge(
      $_CONFIG['defaults'],
      $_CONFIG['application'],
      (array) $_CONFIG[ENVIRONMENT]
   );

   if (PHP_SAPI == 'cli') {
      # Force custom settings when running in a console
      array_update($_CONFIG['application'], array(
         'log_file'          => STDERR,
         'session_store'     => 'none',
         'cache_store'       => 'memory',
         'exception_handler' => false,
         'output_buffering'  => false,
      ));
   }

   function config($key, $subkey=null) {
      if (!array_key_exists($key, $GLOBALS['_CONFIG'])) {
         return $GLOBALS['_CONFIG']['application'][$key];
      } elseif ($subkey) {
         return $GLOBALS['_CONFIG'][$key][$subkey];
      } else {
         return $GLOBALS['_CONFIG'][$key];
      }
   }

   function config_set($key, $value) {
      return $GLOBALS['_CONFIG']['application'][$key] = $value;
   }

   function config_init() {
      $config = config('application');

      # Start output buffering
      if ($config['output_buffering']) {
         ob_start();
      }

      # Configure error reporting
      error_reporting(E_ALL ^ E_NOTICE);
      ini_set('display_errors', (config('debug') or PHP_SAPI == 'cli'));

      # Set global PHP error handler
      if ($handler = $config['error_handler']) {
         set_error_handler($handler, error_reporting());
      }

      # Set global exception handler
      if ($handler = $config['exception_handler']) {
         set_exception_handler($handler);
      }

      # Load routes
      Router::add(config('routes'));

      # Load database support if necessary
      if (config('database')) {
         require LIB.'database/base.php';
      }

      # Configure the logger
      $GLOBALS['_LOGGER'] = new Logger($config['log_file'], $config['log_level']);

      # Setup cache store, always use memory store in console
      load_store('cache', $config['cache_store'], 'memory');

      # Setup session store if enabled and not running in a console
      if ($store = $config['session_store'] and $store != 'none') {
         if ($store != 'php' and $store = load_store('session', $store, 'php')) {
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
      textdomain($config['name']);
      bindtextdomain($config['name'], LANG);
      bind_textdomain_codeset($config['name'], 'UTF-8');

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
      fix_magic_quotes();
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
