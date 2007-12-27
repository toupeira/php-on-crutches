<?# $Id$ ?>
<?

  # Log levels
  define('LOG_INFO',  0);
  define('LOG_WARN',  1);
  define('LOG_ERROR', 2);
  define('LOG_DEBUG', 3);

  # Create the global logger instance
  $logger = new Logger(
    any(config('log_file'), LOG.'application.log'),
    any(config('log_level'), LOG_INFO)
  );

  # Wrappers for all log levels
  function log_info($msg) { return $GLOBALS['logger']->log($msg, LOG_INFO); }
  function log_warn($msg) { return $GLOBALS['logger']->log($msg, LOG_WARN); }
  function log_error($msg) { return $GLOBALS['logger']->log($msg, LOG_ERROR); }
  function log_debug($msg) { return $GLOBALS['logger']->log($msg, LOG_DEBUG); }

  # Dump values to logfile
  function log_dump($data) { return $GLOBALS['logger']->log(var_export($data, true), LOG_DEBUG); }

  class Logger extends Object
  {
    private $level;
    private $file;
    private $buffer;

    function __construct($file, $level) {
      $this->file = $file;
      $this->level = is_numeric($level) ? $level : config('log_level');
    }

    function __destruct() {
      if (is_resource($this->buffer)) {
        fclose($this->buffer);
      }
    }

    function log($msg, $level=LOG_INFO) {
      if (!is_resource($this->buffer)) {
        if (($this->buffer = fopen($this->file, 'a')) === false) {
          $GLOBALS['logger'] = null;
          raise("Couldn't open logfile {$this->file}");
        }
      }

      if ($level <= $this->level) {
        if (is_resource(STDERR)) {
          print "$msg\n";
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
