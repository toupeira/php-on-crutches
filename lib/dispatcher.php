<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Dispatcher
   {
      static public $path;
      static public $prefix;

      static public $controller;
      static public $params;

      # Run a request for the given path.
      #
      # If $path is empty, the path in the query string, the default path
      # in the configuration, and the path 'index' will be tried, in that order.
      #
      static function run($path) {
         self::$path = $path;

         # Detect the relative path used to reach the website
         if (!self::$prefix) {
            self::$prefix = preg_replace(
               '#(index\.(php|fcgi))?(\?[^/]*)?('.self::$path.')?(\?.*)?$#', '',
               $_SERVER['REQUEST_URI']
            );
         }

         # Log request header
         log_debug(
            "\nProcessing {$_SERVER['REQUEST_URI']} "
            . "(for {$_SERVER['REMOTE_ADDR']} at ".strftime("%F %T").") "
            . "[{$_SERVER['REQUEST_METHOD']}]"
         );
         log_debug("  Prefix: ".self::$prefix);
         if (config('use_sessions')) {
            log_debug("  Session ID: ".session_id());
         }

         $params = Router::recognize($path);
         $params = self::$params = array_merge($_GET, $_POST, $params);

         # Log request parameters
         log_info("  Parameters: ".str_replace("\n", "\n  ",
            var_export(self::$params, true)));

         if ($controller = $params['controller']) {
            $class = camelize($controller).'Controller';
            $action = $params['action'] = any($params['action'], 'index');

            self::$controller = new $class($params);
            self::$params = &$params;
            self::$controller->perform($action, explode('/', $params['id']));

            print self::$controller->output;
            return self::$controller;;
         } else {
            raise(new RoutingError("Invalid path '$path'"));
         }
      }
   }

?>
