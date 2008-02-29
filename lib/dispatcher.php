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

         try {
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
               if (count($args) == 1) {
                  $args = explode('/', array_shift($args));
               }

               $class = camelize($controller).'Controller';

               if (class_exists($class)) {
                  self::$controller = new $class(self::$params);
                  self::$controller->perform($action, $args);

                  print self::$controller->output;
                  return self::$controller;;
               }
            }

            raise(new RoutingError("Invalid path '$path'"));

         } catch (NotFound $exception) {
            $status = 404;
            $text = "Not Found";
         } catch (Exception $exception) {
            $status = 500;
            $text = "Server Error";
         }

         header("Status: $status");
         if (config('debug')) {
            print dump_error($exception);
         } elseif (function_exists('rescue_error_in_public')) {
            rescue_error_in_public($exception);
         } elseif ($template = View::find_template("errors/$status")) {
            $view = new View($template);
            print $view->render();
         } else {
            print "<h1>$status $text</h1>";
         }

         return $status;
      }
   }

?>
