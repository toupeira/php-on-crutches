<?# $Id$ ?>
<?

  $_CONFIG = array(
    # Application name
    'application'     => '',
    # Application version
    'version'         => '',

    # Default site
    'default_path'    => '',
    # mod_rewrite or something similar configured
    'rewrite_urls'    => true,
    # Start sessions
    'use_sessions'    => true,

    # Log file
    'log_file'        => LOG.'phpcrutch.log',
    # Log level, see lib/logger.php
    'log_level'       => LOG_WARN,
    # Log SQL queries
    'log_sql'         => false,

    # Show debug information
    'debug'           => false,
    # Show redirects with link
    'debug_redirects' => false,
  );

?>
