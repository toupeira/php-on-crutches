<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Additional log levels
   define('LOG_DISABLED', -1);
   define('LOG_ERROR',      LOG_ERR);
   define('LOG_WARN',       LOG_WARNING);

   # A few wrappers
   function log_error($msg) { return $GLOBALS['logger']->log($msg, LOG_ERROR); }
   function log_warn($msg)  { return $GLOBALS['logger']->log($msg, LOG_WARN); }
   function log_info($msg)  { return $GLOBALS['logger']->log($msg, LOG_INFO); }
   function log_debug($msg) { return $GLOBALS['logger']->log($msg, LOG_DEBUG); }
   function log_dump($data) { return log_debug(var_export($data, true)); }

   function log_running() {
      $logger = $GLOBALS['logger'];
      return $logger instanceof Logger and $logger->running();
   }

   class Logger extends Object
   {
      private $file;
      private $level;
      private $buffer;

      static function init() {
         # Configure error reporting
         if (config('debug') or PHP_SAPI == 'cli') {
            error_reporting(E_ALL ^ E_NOTICE);
            ini_set('display_errors', 1);
         } else {
            error_reporting(0);
            ini_set('display_errors', 0);
         }

         # Create the global logger instance
         if (PHP_SAPI == 'cli') {
            $log_file = STDERR;
         } else {
            $log_file = any(config('log_file'), LOG.'application.log');
         }

         $GLOBALS['logger'] = new Logger(
            $log_file, any(config('log_level'), LOG_INFO)
         );
      }

      function __construct($file=STDERR, $level=LOG_INFO) {
         if (is_resource($file)) {
            $this->buffer = $file;
         } else {
            $this->file = $file;
         }

         if (is_numeric($level)) {
            $this->level = $level;
         }
      }

      function __destruct() {
         if ($this->running()) {
            fclose($this->buffer);
         }
      }

      function running() {
         return is_resource($this->buffer);
      }

      function get_file() {
         return $this->file;
      }

      function set_file($file) {
         $this->__destruct();
         $this->file = $file;
      }

      function get_level() {
         return $this->level;
      }

      function set_level($level) {
         $this->level = intval($level);
      }

      function log($msg, $level=LOG_INFO) {
         if ($level <= $this->level) {
            if (!$this->running()) {
               if (($this->buffer = @fopen($this->file, 'a')) === false) {
                  print "<p><b>Warning:</b> the logfile <tt>{$this->file}</tt> is not writable</p>";
                  $this->level = LOG_DISABLED;
                  return false;
               }
            }

            if (fwrite($this->buffer, "$msg\n") === false) {
               raise("Couldn't write to logfile {$this->file}", false);
            }
            if (fflush($this->buffer) === false) {
               raise("Couldn't flush logfile {$this->file}", false);
            }

            return true;
         }
      }
   }

?>