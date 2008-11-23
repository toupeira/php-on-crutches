#!/usr/bin/rlwrap php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   if (!is_resource(STDIN)) {
      die("Can't read standard input!\n");
   }

   $log_level = LOG_INFO;

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-v':
            # Be verbose
            $log_level = LOG_DEBUG;
            break;
         case '-q':
            # Don't show log messages
            $log_level = LOG_DISABLED;
            break;
         case '-s':
            # Don't show prompts
            define('SILENT', true);
            break;
         case '-e':
            # Execute given command and exit
            if ($command = array_shift($args)) {
               $quiet = ($log_level == LOG_DISABLED ? '-q' : '');
               system("echo '$command' | php5 {$argv[0]} -s $quiet", $status);
               exit($status);
            } else {
               usage();
            }
            break;
         case '-p':
            $_ENV['ENVIRONMENT'] = 'production';
            break;
         case '-d':
            $_ENV['ENVIRONMENT'] = 'development';
            break;
         case '-t':
            $_ENV['ENVIRONMENT'] = 'test';
            break;
         default:
            usage();
      }
   }

   unset($args);
   unset($arg);

   require_once dirname(__FILE__).'/../script.php';

   log_level_set($log_level);
   unset($log_level);

   prompt("\n\n".`php -v`."\n");
   prompt("Loading [1m".ENVIRONMENT."[0m environment\n\n");

   while (!feof(STDIN)) {
      prompt("php[".config('name')."] >>> ");
      $_command = trim(fgets(STDIN));

      # Show help
      if (in_array($_command, array('?', 'help'))) {
         print "\n";
         print "  Special commands:\n";
         print "    help, ?            Show this help\n";
         print "    help FUNCTION      Show function signature\n";
         print "    exit, quit         Exit the console\n";
         print "    time [COUNT] CODE  Show execution time\n";
         print "    ls, cd             Shell aliases\n";
         print "    \$var?              Dump variable\n";
         print "\n";
         print "  Helpers:\n";
         print "    get(\$path, \$params)\n";
         print "    post(\$path, \$params)\n";
         print "\n";
         continue;
      }

      # Show function help
      elseif (substr($_command, 0, 5) == 'help ') {
         print dump_function(substr($_command, 5));
         continue;
      }

      # Exit the console
      elseif (in_array($_command, array('exit', 'quit'))) {
         exit;
      }

      # Show execution time
      elseif (substr($_command, 0, 5) == 'time ') {
         $_command = substr($_command, 5);
         if (($_count = (int) $_command) > 0) {
            if ($_command = substr($_command, strlen($_count))) {
               $_command = "for (\$_i = 0; \$_i < $_count; \$_i++) { $_command; }";
            }
         }

         if ($_command) {
            $_command = 'print ""; $_start = microtime(true); '.$_command
                     . '; printf("\n%.5f seconds\n", microtime(true) - $_start)';
         }
      }

      # 'ls' wrapper
      elseif (trim(substr($_command, 0, 3)) == 'ls') {
         print `$_command -x --color`;
         continue;
      }

      # 'cd' wrapper
      elseif (substr($_command, 0, 3) == 'cd ') {
         chdir(substr($_command, 3));
         print getcwd()."\n";
         continue;
      }

      # 'cd ..' wrapper
      elseif ($_command == '..') {
         chdir('..');
         print getcwd()."\n";
         continue;
      }

      # Execute files
      elseif (substr($_command, 0, 1) == '!') {
         $_shell_command = substr($_command, 1);
         $_command = 'system($_shell_command)';
      }

      # Dump variable
      elseif (preg_match('/^\$\w+\?$/', $_command)) {
         $_command = 'dump_value('.rtrim($_command, '?').')';
      }

      # Execute normal PHP code
      else {
         $_command = rtrim($_command, ';');
      }

      if ($_command) {
         # Check for statements and definitions, which don't have a return value
         if (preg_match('/^([a-z]+) /', $_command, $_m) and !in_array($_m[1], array('new', 'null'))) {
            $_result = 'statement';
         } else {
            $_command = "\$_result = ($_command)";
         }

         # Evaluate the command and capture the output
         try {
            ob_start();
            eval("$_command;");
         }

         # Dump exceptions with colored backtrace
         catch (Exception $_e) {
            print dump_exception($_e);
            $_result = $_e;
         }

         # Show command output
         if ($_output = ob_get_clean()) {
            print rtrim($_output)."\n";
         }

         # Show return value
         prompt(" :: [0;36m".dump_value($_result)."[0m\n");
      }

      $_ = $_result;
   }

   # Generate a fake error so the fatal error handler
   # won't pick up syntax errors from eval
   $fatal_error_handler['shut_up'];

   prompt("\n");
   exit;

   # Helper functions

   function usage() {
      print "Usage: {$GLOBALS['argv'][0]} [OPTIONS]\n"
          . "\n"
          . "  -v       Show debug messages\n"
          . "  -q       Don't show log messages\n"
          . "  -s       Don't show prompt\n"
          . "  -e CODE  Execute code and exit\n"
          . "\n"
          . "  -p       Load production environment\n"
          . "  -d       Load development environment\n"
          . "  -t       Load test environment\n"
          . "\n";
      exit(255);
   }

   function prompt($message) {
      if (!defined('SILENT')) {
         print $message;
      }
   }

   # Perform a request for the given path, with the given HTTP method
   function request($method, $path) {
      $_SERVER['REQUEST_METHOD'] = $method;
      $_SERVER['REQUEST_URI'] = "/$path";
      return $GLOBALS['_controller'] = Dispatcher::run($path);
   }

   # Wrapper for GET requests
   function get($path, array $params=null) {
      if (is_array($params)) {
         $_GET = $params;
         $_POST = array();
      }
      return request('GET', $path);
   }

   # Wrapper for POST requests
   function post($path, array $params=null) {
      $_GET = $_POST = (array) $params;
      return request('POST', $path);
   }

   # Follow a redirect
   function follow() {
      if ($c = $GLOBALS['_controller'] and
          $location = $c->headers['Location'] and
          $c->headers['Status'])
      {
         $path = str_replace('http://www.example.com/', '', $location);
         return get($path);
      }
   }

?>
