<?# $Id$ ?>
<?

  class Object
  {
    function __get($key) {
      $getter = "get_$key";
      if (method_exists($this, $getter)) {
        return $this->$getter();
      } else {
        raise("Can't get property '$key'");
      }
    }

    function __set($key, $value) {
      $setter = "set_$key";
      if (method_exists($this, $setter)) {
        $this->$setter($value);
        return $this;
      } else {
        raise("Can't set property '$key'");
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

?>
