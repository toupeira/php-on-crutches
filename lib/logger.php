<?# $Id$ ?>
<?

  # Log levels
  define('LOG_DISABLED', -1);
  define('LOG_ERROR',    0);
  define('LOG_WARN',     1);
  define('LOG_INFO',     2);
  define('LOG_DEBUG',    3);

  # Create the global logger instance
  if (is_resource(STDIN)) {
    $log_file = STDERR;
  } else {
    $log_file = any(config('log_file'), LOG.'application.log');
  }

  $logger = new Logger(
    $log_file, any(config('log_level'), LOG_INFO)
  );

  # Wrappers for all log levels
  function log_error($msg) { return $GLOBALS['logger']->log($msg, LOG_ERROR); }
  function log_warn($msg) { return $GLOBALS['logger']->log($msg, LOG_WARN); }
  function log_info($msg) { return $GLOBALS['logger']->log($msg, LOG_INFO); }
  function log_debug($msg) { return $GLOBALS['logger']->log($msg, LOG_DEBUG); }

  # Dump values to logfile
  function log_dump($data) { return $GLOBALS['logger']->log(var_export($data, true), LOG_DEBUG); }

  class Logger extends Object
  {
    private $file;
    private $level;
    private $buffer;

    function __construct($file=STDERR, $level=LOG_INFO) {
      if (is_resource($file)) {
        $this->buffer = $file;
      } else {
        $this->file = $file;
      }

      if (is_numeric($level)) {
        $this->level = $level;
      }
    }

    function __destruct() {
      if (is_resource($this->buffer)) {
        fclose($this->buffer);
      }
    }

    function get_file() {
      return $this->file;
    }

    function set_file($file) {
      $this->__destruct();
      $this->file = $file;
    }

    function get_level() {
      return $this->level;
    }

    function set_level($level) {
      $this->level = intval($level);
    }

    function log($msg, $level=LOG_INFO) {
      if ($level <= $this->level) {
        if (!is_resource($this->buffer)) {
          if (($this->buffer = fopen($this->file, 'a')) === false) {
            raise("Couldn't open logfile {$this->file}");
          }
        }

        if (fwrite($this->buffer, "$msg\n") === false) {
          raise("Couldn't write to logfile {$this->file}");
        }
        if (fflush($this->buffer) === false) {
          raise("Couldn't flush logfile {$this->file}");
        }
      }
    }
  }

?>
