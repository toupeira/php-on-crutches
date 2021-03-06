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

   function log_msg($msg, $level=LOG_INFO) {
      if ($logger = $GLOBALS['_LOGGER']) {
         return $logger->log($msg, $level);
      } else {
         return false;
      }
   }

   function log_error($msg) { return log_msg($msg, LOG_ERROR); }
   function log_warn($msg)  { return log_msg($msg, LOG_WARN); }
   function log_info($msg)  { return log_msg($msg, LOG_INFO); }
   function log_debug($msg) { return log_msg($msg, LOG_DEBUG); }

   function log_running() {
      return $GLOBALS['_LOGGER'] instanceof Logger;
   }

   function log_level($level=null) {
      if (is_null($level)) {
         return $GLOBALS['_LOGGER']->level;
      } else {
         return $level <= $GLOBALS['_LOGGER']->level;
      }
   }

   function log_level_set($level) {
      return $GLOBALS['_LOGGER']->level = $level;
   }

   function log_file_set($file) {
      return $GLOBALS['_LOGGER']->file = $file;
   }

   class Logger extends Object
   {
      static protected $_messages;

      protected $_file;
      protected $_level;
      protected $_buffer;

      static function messages() {
         return self::$_messages;
      }

      function __construct($file=STDERR, $level=LOG_INFO) {
         $this->file = $file;

         if (is_numeric($level)) {
            $this->_level = $level;
         }
      }

      function __destruct() {
         if ($this->running) {
            @fclose($this->_buffer);
         }
      }

      function get_running() {
         return (bool) @is_resource($this->_buffer);
      }

      function get_file() {
         return $this->_file;
      }

      function set_file($file) {
         $this->__destruct();

         if (is_resource($file)) {
            $this->_buffer = $file;
            $this->_file = null;
         } else {
            $this->_file = $file;
            $this->_buffer = null;
         }
      }

      function get_level() {
         return $this->_level;
      }

      function set_level($level) {
         $this->_level = round($level);
      }

      function log($msg, $level=LOG_INFO) {
         if ($level <= $this->_level) {
            if (!$this->running) {
               if (!is_resource($this->_buffer = @fopen($this->_file, 'a'))) {
                  print "<p><b>Warning:</b> the logfile <tt>{$this->_file}</tt> is not writable</p>";
                  $this->_level = LOG_DISABLED;
                  return false;
               } elseif (posix_getuid() === 0) {
                  # Make sure the logfile is writable by the webserver
                  chmod($this->_file, 0660);
               }
            }

            if (is_array($msg)) {
               $msg = print_r($msg, true);
            }

            if (config('debug_toolbar')) {
               self::$_messages[] = $msg;
            }

            if (fwrite($this->_buffer, "$msg\n") === false) {
               throw new ApplicationError("Couldn't write to logfile {$this->_file}", false);
            }

            if (fflush($this->_buffer) === false) {
               throw new ApplicationError("Couldn't flush logfile {$this->_file}", false);
            }

            return true;
         } else {
            return false;
         }
      }
   }

?>
