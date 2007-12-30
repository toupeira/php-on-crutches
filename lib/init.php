<?# $Id$ ?>
<?

  require LIB.'util.php';
  require LIB.'config.php';
  require LIB.'logger.php';

  # Configure error reporting
  if (config('debug') or is_resource(STDIN)) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', 1);
  } else {
    error_reporting(0);
    ini_set('display_errors', 0);
  }

  # Start sessions if enabled and not running in a console
  if (config('use_sessions') and !is_resource(STDIN)) {
    session_start();
  }

  # Work around magic quotes
  if (get_magic_quotes_gpc()) {
    foreach ($_POST as $key => $value) {
      if (!is_array($value)) {
        $_POST[$key] = stripslashes($value);
      }
    }
  }

  # Load standard helpers
  $helpers = glob(LIB.'helpers/*.php');
  foreach ($helpers as $helper) {
    require $helper;
  }

  # Load application helper
  @include_once HELPERS.'application_helper.php';

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

?>
