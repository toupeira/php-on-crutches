<?

   $_CONFIG['production'] = array(
      'log_level'          => LOG_WARN,

      'session_store'      => 'php',
      'cache_store'        => 'memory',

      'send_mails'         => true,
      'form_token'         => true,
      'merge_assets'       => true,
      'cache_views'        => true,

      'notify_errors'      => '',
   );

?>
