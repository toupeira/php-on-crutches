#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function usage() {
      print "Usage: {$GLOBALS['argv'][0]} [OPTIONS] CODE\n"
            . "\n"
            . "  -e ENV   Use specific environment\n"
            . "  -p       Use production environment\n"
            . "  -d       Use development environment\n"
            . "  -t       Use test environment\n"
            . "\n"
            . "  -f       Fork to background\n"
            . "\n";
      exit(255);
   }

   $args = array_slice($argv, 1);
   $code = array();
   $fork = false;

   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-e':
            if ($env = array_shift($args)) {
               $_ENV['ENVIRONMENT'] = $env;
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
         case '-f':
            $fork = true;
            break;
         default:
            if ($arg[0] == '-') {
               usage();
            } else {
               $code[] = $arg;
            }
      }
   }

   if ($code) {
      require_once dirname(__FILE__).'/../script.php';
      log_level_set(config('application_default', 'log_level'));
      log_file_set(config('application_default', 'log_file'));

      if ($fork) {
         $pid = pcntl_fork();
         if ($pid < 0) {
            exit(1);
         } elseif ($pid) {
            print "$pid\n";
            exit(0);
         }

         if (!posix_setsid()) {
            print "Couldn't detach process\n";
            exit(1);
         }

         if (is_resource(STDOUT)) {
            fclose(STDOUT);
         }

         $GLOBALS['_JOB_RUNNING'] = true;

         function signal_job_termination() {
            $GLOBALS['_JOB_RUNNING'] = false;
         }

         declare(ticks=1);
         pcntl_signal(15, signal_job_termination);
      }

      try {
         eval(implode("; ", $code).";");
         $status = 0;
      } catch (Exception $e) {
         print dump_exception($e);
         $status = 1;
      }

      exit($status);
   } else {
      usage();
   }

?>
