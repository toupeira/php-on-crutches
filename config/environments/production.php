<?

   $_CONFIG['production'] = array(
      # Log level
      'log_level'         => LOG_WARN,

      # Session store (php, cookie, cache, database)
      'session_store'     => 'php',

      # Cache store (memory, file, apc, or xcache)
      'cache_store'       => 'memory',

      # Send emails
      'send_mails'        => true,

      # Request forgery protection
      'form_token'        => true,

      # Merge CSS and JS files
      'merge_assets'      => true,

      # Send emails to admin_email on exceptions
      'notify_exceptions' => '',
   );

?>
