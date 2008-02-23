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

   if (in_array('-d', $argv)) {
      config_set('debug', true);
      $logger->level = LOG_DEBUG;
   } else {
      $logger->level = LOG_DISABLED;
   }

   # Fake request information
   $_SERVER['REQUEST_URI'] = "/";
   $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
   $_SERVER['REQUEST_METHOD'] = 'GET';
   $_SERVER['HTTPS'] = 'on';

   function request($path, $method) {
      $_SERVER['REQUEST_URI'] = "/$path";
      $_SERVER['REQUEST_METHOD'] = $method;
      print Dispatcher::run($path);
   }

   function get($path, $params=null) {
      if (is_array($params)) {
         $_GET = $_POST = $params;
      }
      request($path, 'GET');
   }

   function post($path, $params=null) {
      $_GET = $_POST = (array) $params;
      request($path, 'POST');
   }

   function to_string($value) {
      if (is_object($value)) {
         return get_class($value);
      } else {
         return var_export($value, true);
      }
   }

   function dump_function($function) {
      print "\n  ";
      try {
         $reflect = new ReflectionFunction($function);
      } catch (Exception $e) {
         print "Function [1m{$function}()[0m does not exist\n\n";
         return;
      }

      print "[1m{$function} ([0m ";
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
            $signature = "[0;32m[ $signature ][0m";
         } else {
            $signature = "[0;36m{$signature}[0m";
         }
         $signatures[] = $signature;
      }
      print implode(', ', $signatures);
      print " [1m)[0m\n\n";
   }

   print "\n\n".`php -v`."\n";
   while (!feof(STDIN)) {
      print "php[".config('application')."] >>> ";
      $_command = trim(fgets(STDIN));

      if ($_command == 'quit') {
         exit;
      } elseif (in_array($_command, array('?', 'help'))) {
         print "\n";
         print "  Special commands:\n";
         print "    help, ?          Show this help\n";
         print "    help FUNCTION    Show function signature\n";
         print "    exit, quit       Exit the console\n";
         print "    time CODE        Show execution time\n";
         print "    ls, cd           Shell aliases\n";
         print "    \$var?            Dump variable\n";
         print "\n";
         print "  Helpers:\n";
         print "    get(\$path, \$params)\n";
         print "    post(\$path, \$params)\n";
         print "\n";
         continue;
      } elseif (substr($_command, 0, 5) == 'help ') {
         dump_function(substr($_command, 5));
         continue;
      } elseif (substr($_command, 0, 5) == 'time ') {
         $_command = 'print ""; $__start = microtime(true); '.substr($_command, 5)
                  . '; printf("\n%.5f seconds\n", microtime(true) - $__start)';
      } elseif ($_command == 'ls' or substr($_command, 0, 3) == 'ls ') {
         print `$_command -x --color`;
         continue;
      } elseif (substr($_command, 0, 3) == 'cd ') {
         chdir(substr($_command, 3));
         print getcwd()."\n";
         continue;
      } elseif ($_command == '..') {
         chdir('..');
         print getcwd()."\n";
         continue;
      } elseif (preg_match('/^\$\w+\?$/', $_command)) {
         $_command = "var_export(".rtrim($_command, '?').")";
      } else {
         $_command = rtrim($_command, ';');
      }

      $_result = null;

      if ($_command) {
         if (preg_match('/^([a-z]+) /', $_command, $_m) and !in_array($_m[1], array('new', 'null'))) {
            $_result = true;
         } else {
            $_command = "\$_result = ($_command)";
         }

         ob_start();

         try {
            eval("$_command;");
         } catch (Exception $_e) {
            print "[1;31m".get_class($_e)."[0m: [1m".$_e->getMessage()."[0m\n";
            foreach (explode("\n", $_e->getTraceAsString()) as $_line) {
               list($_line, $_text) = explode(' ', $_line, 2);
               print "   [1m{$_line}[0m {$_text}\n";
            }
            $_result = $_e;
         }

         if ($_output = ob_get_clean()) {
            print rtrim($_output)."\n";
         }

         print " :: [0;36m".to_string($_result)."[0m\n";
      }

      $_ = $_result;
   }
   print "\n";

# vim: ft=php
?>
