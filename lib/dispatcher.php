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

      static public $controller;
      static public $params;

      static public $start_time = 0;
      static public $db_queries = 0;
      static public $db_queries_sql = array();

      # Run a request for the given path.
      static function run($path) {
         self::$start_time = microtime(true);
         self::$db_queries = 0;
         self::$db_queries_sql = array();

         $path = ltrim($path, '/');
         self::$path = "/$path";
         unset($_GET['path']);

         # Recognize parameters in path
         $params = Router::recognize($path);
         $controller = $params['controller'];
         $action = $params['action'];
         $class = classify($controller.'_controller');
         self::$params = array_merge($_GET, $_POST, $params);

         if (log_level(LOG_INFO)) {
            self::log_header($class, $action);
         }

         # Collect the arguments for the action
         $args = $params;
         unset($args['controller']);
         unset($args['action']);
         if (count($args) == 1) {
            $args = explode('/', array_shift($args));
         }

         # Load the controller and perform the action
         self::$controller = new $class(self::$params);
         self::$controller->perform($action, $args);

         if (log_level(LOG_INFO)) {
            self::log_footer();
         }

         # Print the output
         if (config('debug_toolbar')) {
            # Add the debug toolbar if enabled
            $log_level = log_level();
            log_level_set(LOG_DISABLED);

            $view = new View();
            $toolbar = $view->render('debug/toolbar/index');
            print preg_replace('|(</body>)|', $toolbar.'\1', self::$controller->output);

            log_level_set($log_level);
         } else {
            print self::$controller->output;
         }

         return self::$controller;;
      }

      static function log_header($class, $action) {
         log_info(
            "\nProcessing [0;36m$class#$action[0m (for {$_SERVER['REMOTE_ADDR']} "
            . "at ".strftime("%F %T").") [{$_SERVER['REQUEST_METHOD']}]"
         );

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

         $text .= ' | Size: %.2fK';
         $args[] = strlen(self::$controller->output) / 1024;

         if ($db_queries = Dispatcher::$db_queries) {
            $text .= ' | DB: '.pluralize($db_queries, 'query');
         }

         $text .= ' | Status: %s';
         if ($status == 200) {
            $args[] = "[0;32m$status[0m";
         } else {
            $args[] = "[1;31m$status[0m";
         }

         array_unshift($args, $text);

         log_info(call_user_func_array('sprintf', $args));
      }
   }

?>
