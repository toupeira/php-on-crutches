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
      static public $prefix = '/';

      static public $controller;
      static public $params;

      # Run a request for the given path.
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

         $args = Router::recognize($path);
         $controller = $args['controller'];
         $action = $args['action'];
         self::$params = array_merge($_GET, $_POST, $args);

         # Log request parameters
         log_info("  Parameters: ".str_replace("\n", "\n  ",
            var_export(self::$params, true)));

         if ($controller and $action and $controller != 'application') {
            unset($args['controller']);
            unset($args['action']);

            # Collect the arguments for the controller
            if (count($args) == 1) {
               $args = explode('/', array_shift($args));
            }

            if ($class = classify($controller.'Controller')) {
               # Load the controller and perform the action
               self::$controller = new $class(self::$params);
               self::$controller->perform($action, $args);

               # Print the output
               print self::$controller->output;

               return self::$controller;;
            }
         }

         raise(new RoutingError("Recognition failed for '$path'"));
      }
   }

?>
