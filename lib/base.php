<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

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

   # Raise an exception.
   #
   # $exception can be an instantiated exception, an exception class,
   # or a string with an error message.
   #
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

   # Standard errors
   class ApplicationError extends Exception {};
   class MissingTemplate extends ApplicationError {};

   # Dump an exception with backtrace.
   # Returns the formatted string.
   function dump_error($exception) {
      return "<h1>".humanize(get_class($exception))."</h1>".N
           . "<p>".$exception->getMessage()."</p>".N
           . "<pre>".$exception->getTraceAsString()."</pre>";
   }

   # Return the first non-empty value. Basically a workaround for
   # PHP's broken || operator, which only returns booleans.
   function any() {
      foreach (func_get_args() as $arg) {
         if ($arg) {
            return $arg;
         }
      }
   }

   # Run a shell command silently.
   #
   # $command may contain placeholders which will be replaced
   # by the remaining, shell-escaped arguments.
   #
   # Returns true if the command was successful.
   #
   function run($command) {
      $args = func_get_args();
      $command = array_shift($args);

      if ($args) {
         $args = array_map(escapeshellarg, $args);
         array_unshift($args, $command);
         $command = call_user_func_array(sprintf, $args);
      }

      log_debug("Running '$command'");
      exec("$command 2>/dev/null", $output, $code);
      return ($code === 0);
   }

   # Create a temporary file or directory which will be removed when
   # the request is finished.
   function mktemp($dir=false) {
      $prefix = sys_get_temp_dir();
      $template = config('application').'.XXXXXX';
      $dir = $dir ? '-d' : '';
      $path = trim(`mktemp $dir -p $prefix $template`);
      register_shutdown_function(rm_rf, $path);
      return $path;
   }

   function rm_f($file) {
      if (file_exists($file)) { return @unlink($file); }
   }

   function rm_rf($file) {
      if (file_exists($file)) { return system("rm -rf ".escapeshellarg($file)); }
   }

   # Create something almost, but not quite, entirely unlike a real closure.
   #
   # $code is a string with the function body, and $argc is the number of
   # arguments the function receives. The arguments are named alphabetically,
   # i.e. $a, $b, $c etc.
   #
   # Use it like this:
   #
   #   $square = proc('$a * $a')
   #   $square(2) # -> 4
   #
   # Or in the real-world, to sort an array of people by age:
   #
   #   usort($people, proc('$a->age > $b->age', 2))
   #
   function proc($code, $argc=1) {
      $args = array();
      $argc = min(26, $argc);
      for ($i = 0; $i < $argc; $i++) {
         $args[] = '$'.chr(97 + $i);
      }
      $args = implode(',', $args);
      return create_function($args, "return $code;");
   }

?>
