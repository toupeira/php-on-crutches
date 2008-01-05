<?
/*
  PHP on Crutches - Copyright 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License. See COPYING for details.

  $Id$
*/

  # Log levels
  define('LOG_DISABLED', -1);
  define('LOG_ERROR',    LOG_ERR);
  define('LOG_WARN',     LOG_WARNING);

  # A few wrappers
  function log_error($msg) { return $GLOBALS['logger']->log($msg, LOG_ERR); }
  function log_warn($msg)  { return $GLOBALS['logger']->log($msg, LOG_WARNING); }
  function log_info($msg)  { return $GLOBALS['logger']->log($msg, LOG_INFO); }
  function log_debug($msg) { return $GLOBALS['logger']->log($msg, LOG_DEBUG); }
  function log_dump($data) { return log_debug(var_export($data, true)); }

  class Logger extends Object
  {
    private $file;
    private $level;
    private $buffer;

    static function init() {
      # Configure error reporting
      if (config('debug') or is_resource(STDIN)) {
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', 1);
      } else {
        error_reporting(0);
        ini_set('display_errors', 0);
      }

      # Create the global logger instance
      if (is_resource(STDIN)) {
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
      if (is_resource($this->buffer)) {
        fclose($this->buffer);
      }
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
        if (!is_resource($this->buffer)) {
          if (($this->buffer = fopen($this->file, 'a')) === false) {
            raise("Couldn't open logfile {$this->file}");
          }
        }

        if (fwrite($this->buffer, "$msg\n") === false) {
          raise("Couldn't write to logfile {$this->file}");
        }
        if (fflush($this->buffer) === false) {
          raise("Couldn't flush logfile {$this->file}");
        }
      }
    }
  }

?>
