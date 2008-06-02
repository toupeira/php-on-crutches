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

      static public $start_time;
      static public $render_time;
      static public $db_queries;

      # Run a request for the given path.
      static function run($path) {
         self::$start_time = microtime(true);
         self::$render_time = 0;
         self::$db_queries = 0;

         $path = ltrim($path, '/');
         self::$path = "/$path";
         unset($_GET['path']);

         # Detect the relative path used to reach the website
         self::$prefix = rtrim(preg_replace(
            "#/+(index\.(php|fcgi)(\?[^/]*)?/*)?($path)?(\?.*)?/*$#", '/',
            $_SERVER['REQUEST_URI']
         ), '/').'/';

         $params = Router::recognize($path);
         $controller = $params['controller'];
         $action = $params['action'];
         self::$params = array_merge($_GET, $_POST, $params);

         if ($controller and $action and $controller != 'application') {
            # Collect the arguments for the action
            $args = $params;
            unset($args['controller']);
            unset($args['action']);
            if (count($args) == 1) {
               $args = explode('/', array_shift($args));
            }

            $controller = $controller.'_controller';
            if ($class = classify($controller)) {
               if (config($controller) === false) {
                  throw new RoutingError("$class is disabled");
               }

               if (log_level(LOG_INFO)) {
                  self::log_header($class, $action);
               }

               # Load the controller and perform the action
               self::$controller = new $class(self::$params);
               self::$controller->perform($action, $args);

               # Print the output
               print self::$controller->output;

               if (log_level(LOG_INFO)) {
                  self::log_footer();
               }

               return self::$controller;;
            } else {
               throw new RoutingError("Invalid controller '$path'");
            }
         }

         throw new RoutingError("Recognition failed for '$path'");
      }

      static function log_header($class, $action) {
         log_info(
            "\nProcessing [0;36m$class#$action[0m (for {$_SERVER['REMOTE_ADDR']} "
            . "at ".strftime("%F %T").") [{$_SERVER['REQUEST_METHOD']}]"
         );

         if (config('session_store')) {
            log_info('  Session ID: '.session_id());
         }

         # Log request parameters
         log_info('  Parameters: '.array_to_str(Dispatcher::$params));

         # Log uploaded files, if any
         if ($_FILES) {
            log_info('  Files: '.array_to_str($_FILES));
         }
      }

      static function log_footer() {
         $time = microtime(true) - Dispatcher::$start_time;
         $status = any(Dispatcher::$controller->headers['Status'], 200);
         
         $text = 'Completed in %.5f (%d reqs/sec)';
         $args = array($time, 1/ $time);

         if ($render_time = Dispatcher::$render_time) {
            $text .= ' | Rendering: %.5f (%d%%)';
            $args[] = $render_time;
            $args[] = 100 * $render_time / $time;
         }

         if ($db_queries = Dispatcher::$db_queries) {
            $text .= ' | DB: '.pluralize(Dispatcher::$db_queries, 'query', 'queries');
         }

         $text .= ' | Status: %s';
         if ($status == 200) {
            $args[] = "[0;32m$status[0m";
         } else {
            $args[] = "[1;31m$status[0m";
         }

         array_unshift($args, $text);

         log_info(call_user_func_array(sprintf, $args));
            #'Rendering: %.5f (%d%%) | DB: %.5f (%d%%) | Status: %s',
            #$time, 1 / $time,
            #Dispatcher::$render_time, 100 * Dispatcher::$render_time / $time,
            #Dispatcher::$db_time, 100 * Dispatcher::$db_time / $time,
            #($status == 200 ? "[0;32m$status[0m" : "[1;31m$status[0m")
         #));
      }
   }

?>
