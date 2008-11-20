<?

   $_CONFIG['test'] = array(
      # Don't log anything while testing
      'log_level'         => LOG_DISABLED,

      # Don't start sessions
      'session_store'     => 'none',

      # Cache store (memory, file, apc, or xcache)
      'cache_store'       => 'memory',

      # Don't send emails
      'send_mails'        => false,

      # Request forgery protection
      'form_token'        => false,
   );

?>
