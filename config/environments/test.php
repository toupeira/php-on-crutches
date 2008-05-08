<?

   $_CONFIG['test'] = array(
      # Don't log anything while testing
      'log_level'     => LOG_DISABLED,

      # Don't start sessions
      'session_store' => '',

      # Cache store (memory, file, apc, or xcache)
      'cache_store'   => 'memory',
      # Cache path for file store (Default: ROOT/tmp/cache)
      #'cache_path'    => '',

      # Don't send emails
      'send_mails'    => false,
   );

?>
