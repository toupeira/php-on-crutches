<?# $Id$ ?>
<?

   $_FRAMEWORK = array(
      # Application name
      'application'     => ROOT_NAME,

      # Show debug information
      'debug'           => true,
      # Show redirects with link
      'debug_redirects' => false,

      # Log file
      'log_file'        => LOG.ROOT_NAME.'.log',
      # Log level
      'log_level'       => LOG_DEBUG,

      # Use URL rewriting (needs to be configured on the webserver)
      'rewrite_urls'    => true,

      # Session store (php, cookie, cache, database) (Default: cookie)
      'session_store'   => 'php',

      # Cache store (memory, file, apc, or xcache) (Default: memory)
      'cache_store'     => 'file',
      # Cache path for file store (Default: ROOT/tmp/cache)
      #'cache_path'      => '',

      # Available languages, the first one will be used as default
      #'languages'       => array(),

      # Mailer settings
      #'mail_from'       => '',
      #'mail_from_name'  => '',
      #'mail_sender'     => '',
   );

?>
