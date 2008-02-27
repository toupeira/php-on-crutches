<?# $Id$ ?>
<?

   $_FRAMEWORK = array(
      # Application name
      'application'     => ROOT_NAME,

      # Use URL rewriting (needs to be configured on the webserver)
      'rewrite_urls'    => true,
      # Start sessions
      'use_sessions'    => true,

      # Log file
      'log_file'        => LOG.ROOT_NAME.'.log',
      # Log level
      'log_level'       => LOG_DEBUG,

      # Show debug information
      'debug'           => true,
      # Show redirects with link
      'debug_redirects' => false,
   );

?>
