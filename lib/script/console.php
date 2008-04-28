#!/usr/bin/rlwrap php5
<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../../config/environment.php';

   if (!is_resource(STDIN)) {
      die("Can't read standard input!\n");
   }

   $_environment = 'development';
   config_set('debug', true);
   $_LOGGER->level = LOG_DEBUG;

   function usage() {
      print "Usage: {$GLOBALS['argv'][0]} [OPTIONS]\n"
          . "\n"
          . "  -q       Don't show log messages\n"
          . "  -s       Don't show prompt\n"
          . "  -e CODE  Execute code and exit\n"
          . "\n"
          . "  -p       Enable production mode\n"
          . "  -t       Enable testing mode\n"
          . "\n";
      exit(255);
   }

   $_args = array_slice($argv, 1);
   while ($_arg = array_shift($_args)) {
      switch ($_arg) {
         case '-q':
            # Show log messages
            $_LOGGER->level = LOG_DISABLED;
            break;
         case '-s':
            # Don't show prompts
            define('SILENT', 1);
            break;
         case '-e':
            # Execute given command and exit
            if ($command = array_shift($_args)) {
               $_debug = ($_LOGGER->level == LOG_DEBUG ? '-v' : '');
               system("echo '$command' | php5 {$argv[0]} -s $debug");
               exit;
            } else {
               usage();
            }
            break;
         case '-p':
            $_environment = 'production';
            config_set('debug', false);
            load_store('cache', config('cache_store'), 'memory');
            break;
         case '-t':
            $_environment = 'test';
            define('TESTING', true);
            break;
         default:
            usage();
      }
   }

   fake_request();

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
   function get($path, $params=null) {
      if (is_array($params)) {
         $_GET = $params;
         $_POST = array();
      }
      return request('GET', $path);
   }

   # Wrapper for POST requests
   function post($path, $params=null) {
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

   function to_string($value) {
      if (is_object($value)) {
         return get_class($value);
      } elseif (is_array($value)) {
         ob_start();
         print_r($value);
         return trim(ob_get_clean());
      } elseif (is_resource($value)) {
         ob_start();
         var_dump($value);
         return trim(ob_get_clean());
      } else {
         return var_export($value, true);
      }
   }

   function dump_function($function) {
      print "\n  ";
      try {
         $reflect = new ReflectionFunction($function);
      } catch (Exception $e) {
         print "Function [1m".$function."()[0m does not exist\n\n";
         return;
      }

      print "[1m".$function." ([0m ";
      $signatures = array();
      foreach ($reflect->getParameters() as $param) {
         $signature = '$'.$param->getName();
         if ($param->isPassedByReference()) {
            $signature = "&$signature";
         }
         if ($param->isDefaultValueAvailable()) {
            $signature .= "=".to_string($param->getDefaultValue());
         }
         if ($param->isOptional()) {
            $signature = "[0;32m[ ".$signature." ][0m";
         } else {
            $signature = "[0;36m".$signature."[0m";
         }
         $signatures[] = $signature;
      }
      print implode(', ', $signatures);
      print " [1m)[0m\n\n";
   }

   prompt("\n\n".`php -v`."\n");
   print "Loading [0;36m".$_environment." environment[0m\n";

   while (!feof(STDIN)) {
      prompt("php[".config('application')."] >>> ");
      $_command = trim(fgets(STDIN));

      # Exit the console
      if ($_command == 'quit') {
         exit;

      # Show help
      } elseif (in_array($_command, array('?', 'help'))) {
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

      # Show function help
      } elseif (substr($_command, 0, 5) == 'help ') {
         dump_function(substr($_command, 5));
         continue;

      # Show execution time
      } elseif (substr($_command, 0, 5) == 'time ') {
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

      # 'ls' wrapper
      } elseif ($_command == 'ls' or substr($_command, 0, 3) == 'ls ') {
         print `$_command -x --color`;
         continue;

      # 'cd' wrapper
      } elseif (substr($_command, 0, 3) == 'cd ') {
         chdir(substr($_command, 3));
         print getcwd()."\n";
         continue;

      # 'cd ..' wrapper
      } elseif ($_command == '..') {
         chdir('..');
         print getcwd()."\n";
         continue;

      # Dump variable
      } elseif (preg_match('/^\$\w+\?$/', $_command)) {
         $_command = 'to_string('.rtrim($_command, '?').')';

      # Execute normal PHP code
      } else {
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

         # Dump exceptions with colored backtrace
         } catch (Exception $_e) {
            print "[1;31m".get_class($_e)."[0m: [1m".$_e->getMessage()."[0m\n";

            foreach (explode("\n", $_e->getTraceAsString()) as $_line) {
               list($_line, $_text) = explode(' ', $_line, 2);
               print "   [1m".$_line."[0m ".$_text."\n";
            }

            $_result = $_e;
         }

         # Show command output
         if ($_output = ob_get_clean()) {
            print rtrim($_output)."\n";
         }

         # Show return value
         prompt(" :: [0;36m".to_string($_result)."[0m\n");
      }

      $_ = $_result;
   }

   prompt("\n");

# vim: ft=php
?>
