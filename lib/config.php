<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   #
   # h2. Configuration
   #
   # The following default configuration files live in @APP/config@:
   #
   # * @config/application.php@: framework configuration and custom settings
   # * @config/database.php@: database configuration (see @lib/database/base.php@)
   # * @config/routes.php@: route configuration (see @lib/router.php@)
   #
   # Additionally, if a file exists in @APP/config/environments/@ with the name
   # of the current environment, it will be read and merged with the framework
   # settings.
   #
   # |_(title). Application Settings |_. |_(title). Default |
   # | @name@                 | the application name, will be used as default in some places | basename of root directory |
   # | @prefix@               | the prefix used to reach the website | @/@ |
   # | @languages@            | an array of available languages, with the default first |
   # | @default_languages@    | an array of languages to try as default, for use in custom initializion scripts |
   # | @locale@               | the internal locale, must be a valid and installed locale name | en_US.UTF-8 |
   # |_(title). General Settings  |_. |_(title). Default |
   # | @log_file@             | the path to the log file, or a file handle like STDERR | @ROOT/log/ENVIRONMENT.log@ |
   # | @log_level@            | the log level | @LOG_INFO@ |
   # | @output_buffering@     | enable output buffering | @true@ |
   # | @rewrite_urls@         | generate clean URLs | @true@ |
   # | @error_handler@        | the handler function for PHP errors | @error_handler@ |
   # | @exception_handler@    | the handler function for uncaught exceptions | @exception_handler@ |
   # |_(title). Sessions and Caching |_. |_(title). Default |
   # | @session_store@        | the SessionStore to use | @php@ |
   # | @cache_store@          | the CacheStore to use | @memory@ |
   # | @cache_path@           | the cache directory for CacheStoreFile | @ROOT/tmp/cache@ |
   # | @cache_views@          | enable view caching | @false@ |
   # | @merge_assets@         | automatically combine stylesheets and scripts | @false@ |
   # | @asset_host@           | hostname to use for all assets |
   # |_(title). Mail Settings |_. |_(title). Default |
   # | @send_mails@           | enable sending mails | @true@ |
   # | @mail_from@            | the default sender address |
   # | @mail_from_name@       | the default sender name |
   # | @mail_sender@          | the envelope-from sender |
   # |_(title). Security |_. |_(title). Default |
   # | @form_token@           | automatically add and check form tokens | @false@ |
   # | @form_token_time@      | maximum expiration time for form tokens in seconds, set 0 to disable | @86400@ |
   # | @cookie_defaults@      | the default settings for cookies | @path: /@ |
   # | @auth_model@           | the model to use for authentication |
   # | @auth_controller@      | the controller to use for authentication |
   # | @trusted_hosts@        | hosts and networks which are considered trusted | @127.0.0.1@ |
   # |_(title). Debug |_. |_(title). Default |
   # | @debug@                | show error messages | @false@ |
   # | @debug_toolbar@        | show the debug toolbar | @false@ |
   # | @debug_redirects@      | show links on redirects | @false@ |
   # | @debug_queries@        | analyze database queries | @false@ |
   # | @custom_errors@        | use custom error pages |
   # | @notify_errors@        | addresses to send error notifications to |
   # | @notify_memory@        | log memory usage if it exceeds this limit |
   # | @ignore_errors@        | exceptions to ignore |
   # | @ignore_notifications@ | exceptions which should not be notified |
   # | @custom_mimetypes@     | custom MIME mappings for the mimetype() function |
   #

   # Default framework settings
   $_CONFIG['defaults'] = array(
      'name'                 => basename(ROOT),
      'prefix'               => '/',
      'languages'            => null,
      'locale'               => 'en_US.UTF-8',

      'log_file'             => LOG.ENVIRONMENT.'.log',
      'log_level'            => LOG_INFO,

      'output_buffering'     => true,
      'rewrite_urls'         => true,

      'error_handler'        => 'error_handler',
      'exception_handler'    => 'exception_handler',

      'session_store'        => 'php',
      'cache_store'          => 'memory',
      'cache_path'           => TMP.'cache',
      'cache_views'          => false,
      'merge_assets'         => false,
      'asset_host'           => null,

      'send_mails'           => true,
      'mail_from'            => '',
      'mail_from_name'       => '',

      'form_token'           => false,
      'form_token_time'      => 86400,

      'cookie_defaults'      => array(
         'path' => '/',
      ),

      'auth_model'           => null,
      'auth_controller'      => null,

      'trusted_hosts'        => array('127.0.0.1'),

      'debug'                => false,
      'debug_toolbar'        => false,
      'debug_redirects'      => false,
      'debug_queries'        => false,
      'debug_controller'     => false,

      'custom_errors'        => true,
      'notify_errors'        => null,
      'notify_memory'        => null,

      'ignore_errors'        => array('NotFound', 'AccessDenied', 'ServiceUnavailable'),
      'ignore_notifications' => null,

      'custom_mimetypes'     => array(
         # icons are detected as image/x-ico, which nobody seems to use...
         'ico' => 'image/x-icon',
         # PowerPoint and Excel files are detected as Word documents...
         'pps' => 'application/vnd.ms-powerpoint',
         'ppt' => 'application/vnd.ms-powerpoint',
         'xls' => 'application/vnd.ms-excel',
         'xlt' => 'application/vnd.ms-excel',
         # OOXML files are actually Zip files...
         'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
         'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
         'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
         'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
         'potm' => 'application/vnd.ms-powerpoint.template.macroEnabled.12',
         'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
         'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
         'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
         'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
         'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
         'xltm' => 'application/vnd.ms-excel.template.macroEnabled.12',
         'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
      ),
   );

   # Auto-load class files
   function __autoload($class) {
      $name = underscore($class);

      if (substr($class, -6) == 'Mapper') {
         class_exists(substr($class, 0, -6));
      } elseif (substr($class, -8) == 'QuerySet') {
         class_exists(substr($class, 0, -8));
      } elseif (substr($class, -10) == 'Controller') {
         if (is_file($file = CONTROLLERS."$name.php")) {
            return require $file;
         } elseif (is_file($file = LIB."controllers/$name.php")) {
            return require $file;
         }
      } elseif (is_file($file = MODELS."$name.php")) {
         return require $file;
      } elseif (is_file($file = LIB."models/$name.php")) {
         return require $file;
      }
   }

   function config($key, $subkey=null) {
      $config = &$GLOBALS['_CONFIG'];

      if (!array_key_exists($key, $config)) {
         return $config['application'][$key];
      } elseif ($subkey) {
         return $config[$key][$subkey];
      } else {
         return $config[$key];
      }
   }

   function config_set($key, $value) {
      return $GLOBALS['_CONFIG']['application'][$key] = $value;
   }

   function config_init() {
      global $_CONFIG;

      $_CONFIG_CURRENT = (array) $_CONFIG['application'];

      require CONFIG.'application.php';
      require CONFIG.'routes.php';
      require CONFIG.'database.php';

      if (is_file($config = CONFIG.'environments/'.ENVIRONMENT.'.php')) {
         require $config;
      }

      # Merge application settings
      $_CONFIG['application'] = array_merge(
         $_CONFIG['defaults'],
         $_CONFIG['application'],
         (array) $_CONFIG[ENVIRONMENT],
         (array) $_CONFIG_CURRENT
      );

      if (PHP_SAPI == 'cli') {
         # Force custom settings when running in a console
         $_CONFIG['application_default'] = $_CONFIG['application'];
         array_update($_CONFIG['application'], array(
            'log_file'          => STDERR,
            'session_store'     => 'none',
            'cache_store'       => 'memory',
            'output_buffering'  => false,
            'debug_toolbar'     => false,
            'notify_errors'     => false,
         ));
      }

      $config = config('application');

      # Sanitize the prefix
      config_set('prefix', rtrim($config['prefix'], '/').'/');

      # Start output buffering
      if ($config['output_buffering']) {
         ob_start();
      }

      # Configure the logger
      $GLOBALS['_LOGGER'] = new Logger(
         $config['log_file'],
         $config['log_level']
      );

      # Configure error reporting
      ini_set('display_errors', (config('debug') or PHP_SAPI == 'cli'));

      # Set global PHP error handler
      if ($handler = $config['error_handler']) {
         set_error_handler($handler, error_reporting());
      }

      # Set global exception handler
      if ($handler = $config['exception_handler']) {
         set_exception_handler($handler);

         # Register a shutdown function to catch fatal errors
         register_shutdown_function('fatal_error_handler');
      }

      # Load routes
      Router::add(config('routes'));

      # Load database support if databases are defined
      if (config('database')) {
         require LIB.'database/base.php';
      }

      # Setup cache store, use memory store as default
      load_store('cache', $config['cache_store'], 'memory');

      # Setup the session cookie
      $cookie = '_session';
      if ($name = config('name')) {
         $cookie = "_$name$cookie";
      }
      session_name($cookie);

      if ($defaults = $config['cookie_defaults']) {
         session_set_cookie_params(
            $defaults['lifetime'],
            $defaults['path'],
            $defaults['domain'],
            $defaults['secure'],
            $defaults['httponly']
         );
      }

      # Setup session store if enabled, use cache store as default
      if ($store = $config['session_store'] and $store != 'php') {
         if ($store = load_store('session', $store, 'cache')) {
            session_set_save_handler(
               array($store, 'open'),
               array($store, 'close'),
               array($store, 'read'),
               array($store, 'write'),
               array($store, 'destroy'),
               array($store, 'expire')
            );
         }
      }

      # Setup mbstring
      mb_internal_encoding('UTF-8');

      # Set a valid locale to make sure $LANGUAGE will be respected
      if (!setlocale(LC_MESSAGES, $config['locale']) or
          !setlocale(LC_CTYPE, $config['locale']))
      {
         log_warn("Couldn't load UTF-8 locale");
      }

      # Set C as global locale, to avoid subprocesses inheriting our locale
      putenv("LANG=C");

      # Set the default language if not set already
      if (!config('language')) {
         foreach (array_merge((array) $config['default_languages'], (array) $config['languages']) as $lang) {
            if (set_language($lang)) {
               break;
            }
         }
      }

      # Work around magic quotes...
      fix_magic_quotes();
   }

   # Change the current language used for gettext and templates
   function set_language($lang) {
      if ($lang == 'C' or in_array($lang, config('languages'))) {
         # Configure gettext domain
         $domain = config('name');
         $template = LANG.$lang.'/LC_MESSAGES/'.config('name').'.mo';
         $path = LANG.$lang.'/LC_MESSAGES';

         if (is_file($template = "$path/$domain.mo")) {
            # Add the modification time of the message template to the domain so
            # Gettext will clear its cache and pick up changes without crashing
            $time = filemtime($template);

            # Create the link to the template if necessary
            if (!is_link($link = "$path/$domain-$time.mo")) {
               if (is_writable($path)) {
                  # Remove old links
                  array_map(rm_f, array_filter(glob("$path/$domain-*.mo"), is_link));

                  symlink("$domain.mo", $link);
               } else {
                  print "<p><b>Warning:</b> the directory <tt>{$path}</tt> is not writable</p>";
                  return false;
               }
            }

            $domain .= "-$time";
         }

         bindtextdomain($domain, LANG);
         bind_textdomain_codeset($domain, 'UTF-8');
         textdomain($domain);

         # Using $LANGUAGE allows arbitrary locale names
         putenv("LANGUAGE=$lang");
         config_set('language', $lang);

         return true;
      } else {
         return false;
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
      } elseif ($store == 'none') {
         config_set($type.'_store', '');
         return;
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
