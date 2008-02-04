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
      $command = trim(fgets(STDIN));

      if ($command == 'quit') {
         exit;
      } elseif (in_array($command, array('?', 'help'))) {
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
      } elseif (substr($command, 0, 5) == 'help ') {
         dump_function(substr($command, 5));
         continue;
      } elseif (substr($command, 0, 5) == 'time ') {
         $command = 'print ""; $__start = microtime(true); '.substr($command, 5)
                  . '; printf("\n%.5f seconds\n", microtime(true) - $__start)';
      } elseif ($command == 'ls' or substr($command, 0, 3) == 'ls ') {
         print `$command -x --color`;
         continue;
      } elseif (substr($command, 0, 3) == 'cd ') {
         chdir(substr($command, 3));
         print getcwd()."\n";
         continue;
      } elseif ($command == '..') {
         chdir('..');
         print getcwd()."\n";
         continue;
      } elseif (preg_match('/^\$\w+\?$/', $command)) {
         $command = "var_export(".rtrim($command, '?').")";
      } else {
         $command = rtrim($command, ';');
      }

      $result = null;

      if ($command) {
         if (preg_match('/^([a-z]+) /', $command, $m) and !in_array($m[1], array('new', 'null'))) {
            $result = true;
         } else {
            $command = "\$result = ($command)";
         }

         ob_start();

         try {
            eval("$command;");
         } catch (Exception $e) {
            print "[1;31m".get_class($e)."[0m: [1m".$e->getMessage()."[0m\n";
            foreach (explode("\n", $e->getTraceAsString()) as $line) {
               list($line, $text) = explode(' ', $line, 2);
               print "   [1m{$line}[0m {$text}\n";
            }
            $result = $e;
         }

         if ($output = ob_get_clean()) {
            print rtrim($output)."\n";
         }

         print " :: [0;36m".to_string($result)."[0m\n";
      }

      $_ = $result;
   }
   print "\n";

# vim: ft=php
?>