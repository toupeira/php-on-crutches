<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Job
   {
      # Spawn a background job
      static function spawn($code) {
         $command = build_shell_command(ROOT."script/runner -f -e %s %s", array(ENVIRONMENT, $code));
         log_info("Spawning job '$command'");
         $pid = exec($command, $output, $status);

         if ($status == 0 and is_numeric($pid)) {
            log_info("Started job #$pid");
            return intval($pid);
         } else {
            return false;
         }
      }

      static function running($pid=null) {
         if (func_get_args()) {
            return posix_kill($pid, 0);
         } elseif (array_key_exists('_JOB_RUNNING', $GLOBALS)) {
            return $GLOBALS['_JOB_RUNNING'];
         } else {
            return true;
         }
      }

      static function terminate($pid, $timeout=10) {
         if (is_numeric($pid)) {
            log_info("Terminating job #$pid");
            for ($i = 0; $i < 10 * max(0, $timeout); $i++) {
               posix_kill($pid, 15);
               usleep(100000);
               if (!self::running($pid)) {
                  return true;
               }
            }
         }

         return false;
      }

      static function kill($pid) {
         if (is_numeric($pid)) {
            log_info("Killing job #$pid");
            return posix_kill($pid, 9);
         } else {
            return false;
         }
      }
   }

?>
