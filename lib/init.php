<?# $Id$ ?>
<?

  require LIB.'util.php';
  require LIB.'logger.php';
  require LIB.'config.php';

  # Initialize the logger
  Logger::init();

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

?>
