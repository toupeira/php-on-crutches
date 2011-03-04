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
      static public $status;

      static public $start_time = 0;
      static public $duration;

      static public $db_queries = 0;
      static public $db_queries_sql = array();

      # Run a request for the given path.
      static function run($path) {
         self::$start_time = microtime(true);
         self::$db_queries = 0;
         self::$db_queries_sql = array();

         $path = ltrim(to_utf8($path), '/');
         self::$path = "/$path";
         unset($_GET['path']);

         # Recognize parameters in path
         $params = Router::recognize($path);
         $controller = $params['controller'];
         $action = $params['action'];
         $class = classify($controller.'_controller');
         self::$params = array_merge($_GET, $_POST, $params);

         # Collect the arguments for the action
         $args = $params;
         unset($args['controller']);
         unset($args['action']);
         if (count($args) == 1) {
            $args = explode('/', array_shift($args));
         }

         # Load the controller and perform the action
         self::log_header($class, $action);

         self::$controller = new $class(self::$params);
         self::$controller->perform($action, $args);

         # Set the request duration
         self::$duration = microtime(true) - self::$start_time;

         self::log_footer();

         # Print the output
         if (config('debug_toolbar')) {
            # Add the debug toolbar if enabled
            $log_level = log_level();
            log_level_set(LOG_ERROR);

            $view = new View();
            $toolbar = $view->render('debug/toolbar/index');
            print preg_replace('|(</body>)|', $toolbar.'\1', self::$controller->output);

            log_level_set($log_level);
         } else {
            print self::$controller->output;
         }

         self::log_warnings();

         return self::$controller;
      }

      static function log_header($class, $action, $force=false) {
         if (!$force and !log_level(LOG_INFO)) {
            return;
         }

         $text = "\n[1m{$_SERVER['REQUEST_METHOD']} {$_SERVER['REQUEST_URI']}[0m => "
               . "[0;36m$class#$action[0m (for {$_SERVER['REMOTE_ADDR']} "
               . "at ".strftime("%F %T").")\n";
         $text .= str_repeat('-', strlen($text) - 21);

         # Log User-Agent
         $text .= "\n  User-Agent: {$_SERVER['HTTP_USER_AGENT']}";

         # Log request parameters
         if (is_array(Dispatcher::$params)) {
            $text .= "\n  Parameters: ".array_to_str(Dispatcher::$params);
         }

         # Log uploaded files, if any
         if ($_FILES) {
            $text .= "\n  Files: ".array_to_str($_FILES);
         }

         if ($force) {
            return log_error($text);
         } else {
            return log_info($text);
         }
      }

      static function log_footer($force=false) {
         if (!$force and !log_level(LOG_INFO)) {
            return;
         }

         $status = any(Dispatcher::$status, Dispatcher::$controller->headers['Status'], 200);
         
         if (!is_null(self::$duration)) {
            $text = 'Completed in %.5fs (%d reqs/sec)';
            $args = array(self::$duration, 1 / self::$duration);
         }

         $text .= ' | Size: %.2fK';
         $args[] = strlen(self::$controller->output) / 1024;

         if ($db_queries = Dispatcher::$db_queries) {
            $text .= ' | DB: '.pluralize($db_queries, 'query');
         }

         $text .= ' | Status: %s';
         if (round($status / 100) == 2) {
            $args[] = "[0;32m$status[0m";
         } else {
            $args[] = "[1;31m$status[0m";
         }

         $text = str_repeat('-', strlen($text) + 5)."\n$text";
         array_unshift($args, $text);

         $text = call_user_func_array('sprintf', $args);

         if ($force) {
            return log_error($text);
         } else {
            return log_info($text);
         }
      }

      static function log_warnings() {
         if (is_numeric($time_limit = config('notify_time')) and self::$duration > $time_limit) {
            log_exception(sprintf(
               'Warning - Request time too long: %.2fs / Output: %.2fs',
               self::$duration, microtime(true) - self::$start_time - self::$duration
            ));
         }

         $memory_limit = log_level(LOG_DEBUG) ? 0 : config('notify_memory');

         if (is_numeric($memory_limit)) {
            $memory = memory_get_usage(true);
            $peak = memory_get_peak_usage(true);
            if ($peak > $memory_limit) {
               log_exception(sprintf(
                  'Warning - Memory usage too high: %s / Peak: %s',
                  format_size($memory), format_size($peak)
               ));
            }
         }
      }
   }

?>
