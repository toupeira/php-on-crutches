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
         $path = ltrim($path, '/');
         self::$path = "/$path";

         # Detect the relative path used to reach the website
         self::$prefix = preg_replace(
            "#(index\.(php|fcgi))?(\?[^/]*)?($path)?(\?.*)?$#", '',
            $_SERVER['REQUEST_URI']
         );

         # Log request header
         log_info(
            "\nProcessing {$_SERVER['REQUEST_URI']} "
            . "(for {$_SERVER['REMOTE_ADDR']} at ".strftime("%F %T").") "
            . "[{$_SERVER['REQUEST_METHOD']}]"
         );
         log_debug("  Prefix: ".self::$prefix);

         $args = Router::recognize($path);
         $controller = $args['controller'];
         $action = $args['action'];
         self::$params = array_merge($_GET, $_POST, $args);

         # Log request parameters
         log_info("  Parameters: ".str_replace("\n", "\n  ",
            var_export(self::$params, true)));

         # Log uploaded files, if any
         if ($_FILES) {
            log_info("  Files: ".str_replace("\n", "\n  ",
               var_export($_FILES, true)));
         }

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
            } else {
               throw new RoutingError("Invalid controller '$path'");
            }
         }

         throw new RoutingError("Recognition failed for '$path'");
      }
   }

?>
