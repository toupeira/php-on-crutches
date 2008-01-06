<?
/*
  PHP on Crutches - Copyright (c) 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License. See COPYING for details.

  $Id$
*/

  class Dispatcher extends Object
  {
    static public $path;
    static public $prefix;

    static public $controller;
    static public $action;

    static function init() {
      # Start sessions if enabled and not running in a console
      if (config('use_sessions') and !is_resource(STDIN)) {
        session_start();
      }

      # Work around magic quotes
      if (get_magic_quotes_gpc()) {
        foreach ($_POST as $key => $value) {
          if (!is_array($value)) {
            $_POST[$key] = stripslashes($value);
          }
        }
      }
    }

    static function run($path=null) {
      if (empty($path)) {
        $path = $_GET['path'];
        if (empty($path)) {
          $path = config('default_path');
        }
      }
      unset($_GET['path']);

      self::$path = $path;

      # Detect the relative path used to reach the website
      log_debug($_SERVER['REQUEST_URI']);
      if (!self::$prefix) {
        self::$prefix = preg_replace(
          "#(index\.php)?(\?[^/]*)?($path)?(\?.*)?$#", '',
          $_SERVER['REQUEST_URI']
        );
      }

      # Detect controller, action and arguments
      self::log_header();
      list($controller, $action, $args) = self::recognize($path);
      self::log_request($controller, $action, $args);

      # Perform the action
      $controller->perform($action, $args);

      return $controller->output;
    }

    # Extract controller, action and arguments from a path
    static function recognize($path) {
      $args = explode('/', $path);

      $controller = array_shift($args);
      $action = array_shift($args);

      $class = camelize($controller).'Controller';

      if (!class_exists($class)) {
        $class = 'PagesController';
        $action = 'show';
        $args = rtrim($path, '/');
      }

      if (!ctype_print($action)) {
        $action = 'index';
      }

      $controller = new $class();
      if (is_object($controller)) {
        self::$controller = $controller;
        self::$action = $action;
        return array($controller, $action, $args);
      }

      # Controller not found or invalid
      raise("Invalid controller '$class'");
    }

    # Log request header
    static function log_header() {
      log_debug(
        "\nProcessing {$_SERVER['REQUEST_URI']} "
        . "(for {$_SERVER['REMOTE_ADDR']} at ".strftime("%F %T").") "
        . "[{$_SERVER['REQUEST_METHOD']}]"
      );
      log_debug("  Prefix: ".self::$prefix);
      if (config('use_sessions')) {
        log_debug("  Session ID: ".session_id());
      }
    }

    # Log request details
    static function log_request($controller, $action, $args) {
      log_info("  Controller: {$controller->name}");
      log_info("  Action: $action");
      if (config('debug')) {
        if ($args) {
          log_debug("  Arguments: ".str_replace("\n", "\n  ", var_export($args, true)));
        }
        if ($_REQUEST) {
          log_debug("  Parameters: ".str_replace("\n", "\n  ", var_export($_REQUEST, true)));
        }
      }
    }
  }

?>
