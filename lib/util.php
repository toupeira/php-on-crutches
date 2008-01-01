<?# $Id$ ?>
<?

  # Auto-load libraries, models and controllers
  function __autoload($class) {
    $class = underscore($class);
    if (is_file($file = LIB."$class.php")) {
      require $file;
    } elseif (is_file($file = MODELS."$class.php")) {
      require $file;
    } elseif (substr($class, -10) == 'controller') {
      if (is_file($file = CONTROLLERS."$class.php")) {
        require $file;
      } elseif (is_file($file = LIB."controllers/$class.php")) {
        require $file;
      }
    }
  }

  class Object
  {
    # Call getters
    function __get($key) {
      $getter = "get_$key";
      if (method_exists($this, $getter)) {
        return $this->$getter();
      } else {
        raise("Can't access private property '$key'");
      }
    }

    # Call setters
    function __set($key, $value) {
      $setter = "set_$key";
      if (method_exists($this, $setter)) {
        $this->$setter($value);
        return $this;
      } else {
        raise("Can't change private property '$key'");
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
    log_debug("Running '$command'");
    exec($command, $output, $code);
    return ($code === 0);
  }

  function tempfile() {
    $file = tempnam(sys_get_temp_dir(), config('application').'.');
    register_shutdown_function(rm_f, $file);
    return $file;
  }

  function rm_f($file) {
    if (file_exists($file)) {
      return unlink($file);
    }
  }

  class ApplicationError extends Exception {};
  class MissingTemplate extends ApplicationError {};

  function raise($exception) {
    if ($exception instanceof Exception) {
      $message = get_class($exception);
    } elseif (class_exists($exception)) {
      $exception = new $exception();
      $message = get_class($exception);
    } else {
      $message = $exception;
      $exception = new ApplicationError($message);
    }

    if (is_object($GLOBALS['logger'])) {
      log_error("$message");
    }
    throw $exception;
  }

  function dump_error($exception) {
    return "<h1>".humanize(get_class($exception))."</h1>".N
         . "<p>".$exception->getMessage()."</p>".N
         . "<pre>".$exception->getTraceAsString()."</pre>";
  }

?>
