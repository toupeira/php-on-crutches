<?

   $_CONFIG['production'] = array(
      # Log level
      'log_level'         => LOG_WARN,

      # Session store (php, cookie, cache, database)
      'session_store'     => 'php',

      # Cache store (memory, file, apc, or xcache)
      'cache_store'       => 'memory',
      # Cache path for file store (Default: ROOT/tmp/cache)
      #'cache_path'        => '',

      # Merge CSS and JS files
      'merge_assets'      => true,

      # Send emails
      'send_mails'        => true,

      # Send emails to admin_email on exceptions
      'notify_exceptions' => '',
   );

?>
