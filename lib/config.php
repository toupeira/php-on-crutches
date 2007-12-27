<?# $Id$ ?>
<?

  require_once CONFIG.'config.php';
  @include_once CONFIG.config('application').'.php';

  $_CONFIG = array_merge($_CONFIG, (array) $_APP_CONFIG);

  function config($key) {
    return $GLOBALS['_CONFIG'][$key];
  }

  function config_set($key, $value) {
    $GLOBALS['_CONFIG'][$key] = $value;
  }

?>
