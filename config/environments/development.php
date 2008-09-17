<?

   $_CONFIG['development'] = array(
      # Log level
      'log_level'       => LOG_DEBUG,

      # Session store (php, cookie, cache, database)
      'session_store'   => 'php',

      # Cache store (memory, file, apc, or xcache)
      'cache_store'     => 'memory',
      # Cache path for file store (Default: ROOT/tmp/cache)
      #'cache_path'      => '',

      # Don't send emails
      'send_mails'      => false,

      # Show error messages
      'debug'           => true,

      # Analyze database queries
      'debug_queries'   => false,

      # Show redirects
      'debug_redirects' => false,
   );

?>
