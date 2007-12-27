<?# $Id$ ?>
<?

  class Dispatcher extends Object
  {
    static public $path;
    static public $prefix;

    static public $controller;
    static public $action;

    static function run($path=null) {
      try {
        if (empty($path)) {
          $path = $_GET['path'];
          if (empty($path)) {
            $path = config('default_path');
          }
        }
        unset($_GET['path']);

        self::$path = $path;

        # Detect the relative path used to reach the website
        log_debug($_SERVER['REQUEST_URI']);
        if (!self::$prefix) {
          self::$prefix = preg_replace(
            "#(index\.php)?(\?[^/]*)?($path)?(\?.*)?$#", '',
            $_SERVER['REQUEST_URI']
          );
        }

        log_debug(
          "\nProcessing {$_SERVER['REQUEST_URI']} "
          . "(for {$_SERVER['REMOTE_ADDR']} at ".strftime("%F %T").") "
          . "[{$_SERVER['REQUEST_METHOD']}]"
        );
        log_debug("  Prefix: ".self::$prefix);
        if (config('use_sessions')) {
          log_debug("  Session ID: ".session_id());
        }

        list($controller, $action, $args) = self::recognize($path);

        log_info("  Controller: {$controller->name}");
        log_info("  Action: $action");
        if (config('debug')) {
          if ($args) {
            log_debug("  Arguments: ".str_replace("\n", "\n  ", var_export($args, true)));
          }
          if ($_REQUEST) {
            log_debug("  Parameters: ".str_replace("\n", "\n  ", var_export($_REQUEST, true)));
          }
        }

        return $controller->perform($action, $args);
      } catch (ApplicationError $e) {
        print "<h1>Application Error</h1>".N;
        print "<p>".$e->getMessage()."</p>".N;
        if (config('debug')) {
          print "<pre>".$e->getTraceAsString()."</pre>";
        }
      }
    }

    # Extract controller, action and arguments from a path
    static function recognize($path) {
      $args = explode('/', $path);

      if (ctype_alpha($controller = array_shift($args))) {
        if (!ctype_print($action = array_shift($args))) {
          $action = 'index';
        }
      } else {
        # Use default pages controller
        $controller = 'pages';
        $action = 'show';
        $args = array(implode('/', $args));
      }

      $class = ucfirst(strtolower($controller)).'Controller';
      if (class_exists($class)) {
        $controller = new $class();
        if (is_object($controller)) {
          self::$controller = $controller;
          self::$action = $action;
          return array($controller, $action, $args);
        }
      }

      # Controller not found or invalid
      raise("Invalid controller '$class'");
    }
  }

?>
