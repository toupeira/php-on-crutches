<?
/*
   PHP on Crutches - Copyright (c) 2008 Markus Koller

   This program is free software; you can redistribute it and/or modify
   it under the terms of the MIT License.

   $Id$
*/

   # Auto-load libraries, models and controllers
   function __autoload($class) {
      $class = underscore($class);
      if (is_file($file = MODELS."$class.php")) {
         require $file;
      } elseif (substr($class, -10) == 'controller') {
         if (is_file($file = CONTROLLERS."$class.php")) {
            require $file;
         } elseif (is_file($file = LIB."controllers/$class.php")) {
            require $file;
         }
      } elseif (is_file($file = LIB.str_replace('_', '/', $class).'.php')) {
         require $file;
      }
   }

   # Base class for all objects
   class Object
   {
      # Automatic getters
      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } else {
            $class = get_class($this);
            raise("Call to undefined method $class::$getter()");
         }
      }

      # Automatic setters
      function __set($key, $value) {
         $setter = "set_$key";
         if (method_exists($this, $setter)) {
            $this->$setter($value);
            return $this;
         } else {
            $class = get_class($this);
            raise("Call to undefined method $class::$setter()");
         }
      }

      # Call a function if it is defined
      function call_if_defined($method) {
         if (method_exists($this, $method)) {
            return $this->$method();
         }
      }
   }

   function any() {
      foreach (func_get_args() as $arg) {
         if ($arg) {
            return $arg;
         }
      }
   }

   function run($command) {
      $args = func_get_args();
      $command = array_shift($args);

      if ($args) {
         $args = array_map(escapeshellarg, $args);
         array_unshift($args, $command);
         $command = call_user_func_array(sprintf, $args);
      }

      log_debug("Running '$command'");
      exec($command, $output, $code);
      return ($code === 0);
   }

   function mktemp($dir=false) {
      $prefix = sys_get_temp_dir();
      $template = config('application').'.XXXXXX';
      $dir = $dir ? '-d' : '';
      $path = trim(`mktemp $dir -p $prefix $template`);
      register_shutdown_function(rm_rf, $path);
      return $path;
   }

   function rm_f($file) {
      if (file_exists($file)) {
         return @unlink($file);
      }
   }

   function rm_rf($file) {
      if (file_exists($file)) {
         return system("rm -rf ".escapeshellarg($file));
      }
   }

   class ApplicationError extends Exception {};
   class MissingTemplate extends ApplicationError {};

   function raise($exception, $log=true) {
      if ($exception instanceof Exception) {
         $message = get_class($exception);
      } elseif (class_exists($exception)) {
         $exception = new $exception();
         $message = get_class($exception);
      } else {
         $message = $exception;
         $exception = new ApplicationError($message);
      }

      if ($log and log_running()) {
         log_error("\n".get_class($exception).": $message");
         log_debug("  ".str_replace("\n", "\n  ", $exception->getTraceAsString()));
      }

      throw $exception;
   }

   function dump_error($exception) {
      return "<h1>".humanize(get_class($exception))."</h1>".N
           . "<p>".$exception->getMessage()."</p>".N
           . "<pre>".$exception->getTraceAsString()."</pre>";
   }

?>
