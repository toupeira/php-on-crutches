<?# $Id$ ?>
<?

  abstract class Controller extends Object
  {
    protected $name;

    protected $params;
    protected $cookies;
    protected $files;

    protected $methods;

    protected $output;
    protected $layout;

    protected $verify_post;
    protected $verify_xhr;

    protected $headers = array();
    protected $actions = array();
    protected $data = array();
    protected $errors = array();
    protected $msg = array();

    function __construct() {
      $this->name = strtolower(substr(get_class($this), 0, -10));

      # Load controller helper, ignore errors
      @include_once HELPERS.$this->name.'.php';

      # Shortcuts
      $this->params = array_merge($_GET, $_POST);
      $this->cookies = &$_COOKIES;
      $this->files = &$_FILES;

      # Load messages stored in the session
      if (is_array($_SESSION['msg'])) {
        $this->msg = $_SESSION['msg'];
        session_unregister('msg');
      }

      # Standard variables for the view
      $this->set('controller', $this->name);
      $this->set('params', &$this->params);
      $this->set('cookies', &$this->cookies);
      $this->set('msg', &$this->msg);

      # Collect all methods defined in this controller
      $this->methods = array_diff(
        get_class_methods($this),
        get_class_methods(Controller)
      );

      # Collect all public actions
      foreach ($this->methods as $method) {
        $reflect = new ReflectionMethod($this, $method);
        if ($reflect->isPublic() and !$reflect->isConstructor()) {
          $this->actions[] = $method;
        }
      }
    }

    function get_name() {
      return $this->name;
    }

    function perform($action, $args=null) {
      # Catch invalid action names
      if (!ctype_alpha($action)
         or substr($action, 0, 6) == 'before'
         or substr($action, 0, 5) == 'after') {
        raise("Invalid action '$action'");
      }

      # Check if the request is valid for this action
      if ((!$this->is_post() and in_array($action, (array) $this->verify_post)) or
         (!$this->is_xhr() and in_array($action, (array) $this->verify_xhr))) {
        $this->redirect_to('.');
      }

      $this->set('action', $action);

      # Reset the layout, don't use one for Ajax requests
      if ($this->is_xhr()) {
        $this->layout = null;
      } else {
        $this->layout = 'application';
      }

      # Call before filters
      $this->call_if_defined("before");
      $this->call_if_defined("before_$action");

      # Call the action itself if it's defined
      if (in_array($action, $this->actions)) {
        call_user_func_array(array($this, $action), (array) $args);
      }

      # Call after filters
      $this->call_if_defined("after_$action");
      $this->call_if_defined("after");

      # Send all headers
      $this->send_headers();

      # Render the action template if the action didn't set any output already
      if ($this->output === null) {
        $this->render($action);
      }

      return $this->output;
    }

    # Send the configured headers
    function send_headers() {
      foreach ($this->headers as $header => $value) {
        header("$header: $value");
      }
    }

    # Check if a form value has errors
    function has_errors($key) {
      if (substr($name, -2) == '[]') {
        return in_array(substr($key, 0, -2), $this->errors);
      } else {
        return in_array($key, $this->errors);
      }
    }

    # Get error messages from template objects
    private function set_error_messages() {
      $messages = array();
      foreach ($this->data as $key => $value) {
        if ($value instanceof Model) {
          $messages = array_merge($messages, $value->messages);
        }
      }

      if (count($messages) > 1) {
        $this->msg['error'] = "<ul><li>".implode("</li><li>", $messages)."</li></ul>";
      } elseif (count($messages) > 0) {
        $this->msg['error'] = $messages[0];
      }
    }

    # Get specified parameters from object
    protected function params_filter() {
      $keys = func_get_args();
      $object = array_shift($keys);
      if (is_array($data = $this->params[$object])) {
        $filter = array();
        foreach ($keys as $key) {
          $filter[$key] = $data[$key];
        }
        return $filter;
      } else {
        return null;
      }
    }

    # Set a value for the template
    protected function set($key, $value) {
      $this->data[$key] = &$value;
    }

    # Render an action
    protected function render($action) {
      if ($this->output === null) {
        if (is_file($action)) {
          $template = $action;
        } else {
          $template = $this->find_template($action);
        }

        if ($template) {
          # Render the template with the stored data and layout
          $this->set_error_messages();
          $view = new View($template, $this->data);
          $this->output = $view->render($this->layout);
        } else {
          raise("No template found for action '$action'");
        }
      } else {
        raise("Can only render once per request");
      }
    }

    # Render only the given text without layout
    protected function render_text($text) {
      $this->output = $text;
    }

    # Redirect to a path
    protected function redirect_to($path) {
      $url = url_for($path);

      # Save messages so they can be displayed in the next request
      $this->set_error_messages();
      $_SESSION['msg'] = $this->msg;

      if (config('debug_redirects')) {
        $this->render_text("Redirect to ".link_to($url, $url));
      } else {
        $this->headers['Location'] = $url;
        $this->render_text(' ');
      }
    }

    # Send a file with the appropriate headers
    protected function send_file($file, $options) {
      if (!is_file($file)) {
        raise("File $file not found");
      }

      if (!$options['inline']) {
        $this->headers['Content-Disposition'] = 'attachment';

        if (ctype_print($name = $options['name'])) {
          $name = str_replace('"', '\"', $name);
          $this->headers['Content-Disposition'] .= "; filename=\"$name\"";
        }
      }

      $this->headers['Content-Type'] = any(
        $options['type'], mime_content_type($file)
      );

      $this->headers['Content-Length'] = filesize($file);
      $this->send_headers();
      $this->render_text('');

      if (readfile($file)) {
        return true;
      } else {
        raise("Can't read file $file");
      }
    }

    # Add invalid fields and an error message
    protected function add_error($keys, $message) {
      $this->errors = array_merge($this->errors, (array) $keys);
      $this->msg['error'] = $message;
    }

    # Send a HTTP error code with no output
    protected function error($code) {
      header("Status: $code");
      $this->render_text('');
    }

    # Check if this is a POST request
    protected function is_post() {
      global $_is_post;
      if ($_is_post === null) {
        $_is_post = ($_SERVER['REQUEST_METHOD'] == 'POST');
      }
      return $_is_post;
    }

    # Check if this is an Ajax request
    protected function is_xhr() {
      global $_is_xhr;
      if ($_is_xhr === null) {
        $_is_xhr = (preg_match('/XMLHttpRequest/', $_SERVER['HTTP_X_REQUESTED_WITH']) > 0);
      }
      return $_is_xhr;
    }

    protected function find_template($action) {
      foreach (array(VIEWS, LIB.'views') as $dir) {
        if (is_file($template = "$dir/{$this->name}/$action.thtml")) {
          return $template;
        }
      }

      return null;
    }

    # Call a function if it is defined
    protected function call_if_defined($method) {
      if (in_array($method, $this->methods)) {
        return $this->$method();
      }
    }
  }

?>