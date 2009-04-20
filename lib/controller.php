<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Controller extends Object
   {
      public $params;
      public $session;
      public $headers;
      public $cookies;
      public $files;
      public $msg;

      protected $_name;
      protected $_layout = 'application';
      protected $_view;
      protected $_output;
      protected $_action;
      protected $_cached;

      protected $_actions;
      protected $_errors;

      protected $_start_session = true;

      protected $_require_post;
      protected $_require_ajax;
      protected $_require_ssl;
      protected $_require_trusted;
      protected $_require_form_token = true;
      protected $_valid_methods = array('GET', 'POST', 'HEAD');

      protected $_scaffold;
      protected $_scaffold_options;
      protected $_scaffold_actions = array(
         'index',
         'show',
         'create',
         'edit',
         'destroy',
      );

      function __construct(array &$params=null) {
         $this->_name = underscore(substr(get_class($this), 0, -10));

         if ($this->_scaffold === true) {
            if ($model = classify(pluralize($this->_name))) {
               $this->_scaffold = $model;
            }
         }

         # Load controller helper
         try_require(HELPERS."{$this->_name}_helper.php");

         # Start session if enabled
         if ($this->_start_session and config('session_store')) {
            $this->start_session();
         }

         # Shortcuts for request data
         $this->params = &$params;
         $this->cookies = &$_COOKIE;
         $this->files = &$_FILES;
         $this->session = &$_SESSION;

         # Sanitize uploaded files
         foreach ($this->files as $i => &$file) {
            $file['name'] = basename($file['name']);
            if (!is_uploaded_file($file['tmp_name'])) {
               if ($file['tmp_name']) {
                  log_warn("File injection attack: {$file['tmp_name']}");
               }
               unset($this->files[$i]);
            }
         }

         # Set default headers
         $this->headers = array(
            'Content-Type' => 'text/html',
         );

         # Load messages stored in the session
         if (is_array($this->session['msg'])) {
            $this->msg = $this->session['msg'];
            unset($this->session['msg']);
         }

         # Create the view
         $this->_view = new View();

         # Standard variables for the view
         $this->set('controller', $this->_name);
         $this->set('msg', &$this->msg);

         # Collect all public methods defined in this controller
         $this->_actions = array();
         $class = new ReflectionClass($this);
         foreach ($class->getMethods() as $method) {
            $name = $method->getName();
            if ($method->isPublic() and !$method->isStatic() and
                !method_exists(ApplicationController, $name)
            ) {
               $this->_actions[] = $name;
            }
         }

         # Call custom initializer
         $this->call_if_defined('init');
      }

      function inspect() {
         return parent::inspect($this->params);
      }

      function get_name() {
         return (string) $this->_name;
      }

      function get_layout() {
         return $this->_layout;
      }

      function set_layout($layout) {
         return $this->_layout = $layout;
      }

      function get_view() {
         return $this->_view;
      }

      function get_output() {
         return (string) $this->_output;
      }

      function get_action() {
         return (string) $this->_action;
      }

      function get_cached() {
         return (bool) $this->_cached;
      }

      function get_actions() {
         return (array) $this->_actions;
      }

      function get_errors() {
         return (array) $this->_errors;
      }

      # Get and set template values
      function get($key) {
         return $this->_view->get($key);
      }

      function set($key, $value=null) {
         $this->_view->set($key, &$value);
         return $this;
      }

      function set_default($key, $value=null) {
         $this->_view->set_default($key, &$value);
         return $this;
      }

      # Check if this is a POST request
      function is_post() {
         return $_SERVER['REQUEST_METHOD'] == 'POST';
      }

      # Check if this is an Ajax request
      function is_ajax($method=null) {
         if ($method and $_SERVER['REQUEST_METHOD'] != strtoupper($method)) {
            return false;
         }

         return strstr($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest');
      }

      # Check if SSL is enabled
      function is_ssl() {
         return $_SERVER['HTTPS'] != '' and $_SERVER['HTTPS'] != 'off';
      }

      # Check if the client host is trusted
      function is_trusted($action=null) {
         $hosts = any(
            $this->_require_trusted[$action],
            $this->_require_trusted['all'],
            config('trusted_hosts')
         );

         if (empty($hosts)) {
            throw new ConfigurationError('No hosts given');
         }

         $client = $_SERVER['REMOTE_ADDR'];
         $found = false;
         foreach ((array) $hosts as $host) {
            # Expand wildcards into subnet patterns
            $host = strtr($host, array(
               '*' => '[0-9]+',
               '.' => '\\.',
            ));

            if (preg_match("/^$host$/", $client)) {
               $found = true;
               break;
            }
         }

         return $found;
      }

      # Check if the action exists or is provided by scaffolding
      function has_action($action) {
         return in_array($action, $this->_actions)
            or (in_array($action, (array) $this->_scaffold_actions) and $this->_scaffold);
      }

      # Check for a requirement for the given action
      function check_requirement($action, $requirement) {
         $requirement = '_require_'.$requirement;
         $requirement = $this->$requirement;

         if ($except = $requirement['except']) {
            if (count($requirement) > 1) {
               throw new ApplicationError("Can't combine 'exclude' with other arguments");
            } else {
               return !in_array($action, (array) $except);
            }
         }

         return $requirement === true
             or in_array($action, (array) $requirement)
             or array_key_exists('all', (array) $requirement)
             or array_key_exists($action, (array) $requirement);
      }

      # Check request requirements
      function is_valid_request($action) {
         # Check for SSL requirements
         if (!$this->is_ssl() and $this->check_requirement($action, 'ssl')) {
            $this->redirect_to("https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
            return false;

         # Check for valid methods
         } elseif ($this->_valid_methods !== true and !in_array($_SERVER['REQUEST_METHOD'], $this->_valid_methods)) {
            $error = InvalidRequest;
            $message = "invalid method {$_SERVER['REQUEST_METHOD']}";

         # Check for POST requirements
         } elseif (!$this->is_post() and $this->check_requirement($action, 'post')) {
            $error = InvalidRequest;
            $message = 'needs POST';

         # Check for Ajax requirements
         } elseif (!$this->is_ajax() and $this->check_requirement($action, 'ajax')) {
            $error = InvalidRequest;
            $message = 'needs Ajax';

         # Check for trusted host requirements
         } elseif ($this->check_requirement($action, 'trusted') and !$this->is_trusted($action)) {
            $error = AccessDenied;
            $message = "untrusted host {$_SERVER['REMOTE_ADDR']}";

         # Check for cross site request forgery
         } elseif ($this->is_post() and !$this->is_ajax() and config('form_token') and $this->check_requirement($action, 'form_token')) {
            if (!$this->params['_form_token']) {
               $error = InvalidRequest;
               $message = 'missing form token';
            } elseif ($this->params['_form_token'] != $this->session['form_token']) {
               $error = InvalidRequest;
               $message = 'forged form token';
            } elseif ($max_time = config('form_token_time') and
                      $form_time = $this->session['form_token_time'] and
                      time() - $form_time > $max_time) {
               $error = InvalidRequest;
               $message = 'expired form token';
            }
         }

         if ($error) {
            throw new $error($message);
            return false;
         } else {
            return true;
         }
      }

      # Perform an action
      function perform($action, $args=null) {
         # Catch invalid action names
         if (!preg_match('/^[a-z][\w_]*$/', $action)) {
            throw new RoutingError("Invalid action '$action'");
         }

         if ($this->is_valid_request($action)) {
            $this->_action = $action;
            $this->set('action', $action);

            # Reset the layout for Ajax requests
            if ($this->is_ajax()) {
               $this->_layout = null;
            }

            # Call before filters
            $this->call_filter("global_before", $action);
            $this->call_filter("before", $action);
            $this->call_filter("before_$action");

            # Call the action itself if it's defined
            if (in_array($action, $this->_actions)) {
               $output = call_user_func_array(array($this, $action), (array) $args);
               if (is_string($output) and blank($this->_output)) {
                  $this->_output = $output;
               }
            } elseif ($this->has_action($action)) {
               array_unshift($args, $action);
               call_user_func_array(array($this, 'scaffold'), $args);
            }

            # Call before-render filters
            $this->call_filter("global_before_render", $action);
            $this->call_filter("before_render", $action);
            $this->call_filter("before_render_$action");

            # Render the action template if the action didn't generate any output
            if ($this->_output === null) {
               $this->render($action);
            }

            # Call after filters
            $this->call_filter("after_$action");
            $this->call_filter("after", $action);
            $this->call_filter("global_after", $action);
         }

         $this->send_headers();
         return $this->_output;
      }

      # Render an action
      function render($action, $layout=null) {
         if ($this->_output === null) {
            if (is_array($action) or strstr($action, '/') !== false) {
               $template = $action;
            } else {
               $template = $this->_name.'/'.$action;
            }

            $this->set_model_errors();

            return $this->_output = $this->_view->render(
               $template, (is_null($layout) ? $this->layout : $layout)
            );

         } else {
            throw new ApplicationError("Can only render once per request");
         }
      }

      function cache_render($key=null, $full=false, $expire=0) {
         $key = any($key, "view_".urlencode(Dispatcher::$path));
         if ($this->view->cache($key, $full, $expire) and cache_exists($key)) {
            $this->_cached = true;
            return true;
         } else {
            return false;
         }
      }

      # Render only the given text without layout
      function render_text($text) {
         return $this->_output = $text;
      }

      function render_json($object, $status=200) {
         if (method_exists($object, 'to_json')) {
            $json = $object->to_json();
         } else {
            $json = to_json($object);
         }

         return $this->head($status, $json, 'application/json');
      }

      function render_xml($object, $status=200) {
         if (method_exists($object, 'to_xml')) {
            $xml = $object->to_xml();
         } else {
            $xml = to_xml($object);
         }

         return $this->head($status, $xml, 'text/xml');
      }

      # Redirect to a path
      function redirect_to($path, $code=302) {
         $url = url_for($path, array('full' => true));

         # Save messages so they can be displayed in the next request
         $this->set_model_errors();
         if ($this->msg) {
            $this->session['msg'] = $this->msg;
         }

         log_info("Redirecting to $url");

         if (config('debug_redirects')) {
            $this->render_text("Redirecting to ".link_to(h($url), $url));
         } else {
            $this->headers['Location'] = $url;
            $this->head($code);
         }

         return true;
      }

      # Redirect to the previous page
      function redirect_back($default=null, $options=null) {
         if ($path = $this->session['return_to']) {
            # Use path stored in session
            unset($this->session['return_to']);
         } elseif ($url = $_SERVER['HTTP_REFERER']) {
            # Use the HTTP referer if it points to the current host, and doesn't point to the current path
            $url = parse_url($url);
            if ((!$url['host'] or $url['host'] == $_SERVER['HTTP_HOST']) and stristr($url['path'], Dispatcher::$path) === false) {
               $path = $url['path'];
            }
         }

         if (!$path and !$path = $default) {
            # Use the default page if no path was found
            $path = '';
         }

         return $this->redirect_to(url_for($path, $options));
      }

      # Start the session if necessary
      function start_session() {
         if (!session_id() and PHP_SAPI != 'cli') {
            session_start();
            log_info('  Session ID: '.session_id());

            # Override default no-cache headers, except for Ajax requests
            if (!$this->is_ajax) {
               header('Cache-Control: private');
               header('Pragma: cache');
            }

            # Make sure the session handler can clean up
            register_shutdown_function('session_write_close');
         }
      }

      function head($code, $text=' ', $type='text/plain') {
         $this->headers['Status'] = $code;
         $this->headers['Content-Type'] = $type;
         $this->send_headers();
         $this->render_text($text);
      }

      # Send the configured headers
      function send_headers() {
         foreach ((array) $this->headers as $header => $value) {
            if ($value !== null) {
               if ($header == 'Status') {
                  $header = "HTTP/1.x $value";
               } elseif ($header == 'Content-Type'
                           and substr($value, 0, 5) == 'text/'
                           and strstr($value, '; charset') === false)
               {
                  $header = "$header: $value; charset=utf-8";
               } else {
                  $header = "$header: $value";
               }

               if (PHP_SAPI == 'cli') {
                  # Ignore errors in console
                  @header($header);
               } else {
                  header($header);
               }
            }
         }

         return true;
      }

      # Send an Expires header
      function expires($duration) {
         $this->headers['Expires'] = strftime('%a, %d %b %Y %H:%M:%S %z', time() + $duration);
         return true;
      }

      # Send a cookie
      function send_cookie($name, $value, array $options=null) {
         $options = array_merge((array) config('cookie_defaults'), (array) $options);
         $args = array(
            $name,
            $value,
            $options['expire'],
            $options['path'],
            $options['domain'],
            $options['secure'],
            $options['httponly'],
         );

         if (PHP_SAPI == 'cli') {
            # Ignore errors in console
            return @call_user_func_array('setcookie', $args);
         } else {
            return call_user_func_array('setcookie', $args);
         }
      }

      # Delete a cookie
      function delete_cookie($name, array $options=null) {
         return $this->send_cookie($name, '', array_merge(
            (array) $options, array('expire' => time() - 3600)
         ));
      }

      # Send a file with the appropriate headers
      function send_file($file, array $options=null) {
         unset($this->headers['Content-Type']);

         if ($file[0] == '!') {
            $command = substr($file, 1);
            $file = null;
         } elseif (!is_file($file)) {
            throw new NotFound("File $file not found");
         }

         $this->headers['Content-Disposition'] = ($options['inline'] ? 'inline' : 'attachment');

         if (ctype_print($name = $options['name'])) {
            $name = str_replace('"', '\"', $name);
            $this->headers['Content-Disposition'] .= "; filename=\"$name\"";
         }

         if ($size = $options['size'] or ($file and $size = filesize($file))) {
            $this->headers['Content-Length'] = $size;
         }

         if ($type = $options['type'] or ($file and $type = @mime_content_type($file))) {
            $this->headers['Content-Type'] = $type;
         }

         while (ob_get_level()) {
            ob_end_clean();
         }

         $status = false;
         if ($command) {
            # Execute a command and send the output
            $message = "Sending output of '$command'";
            $this->send_headers();
            passthru("$command 2>/dev/null");
            $status = true;
         } elseif ($options['xsendfile']) {
            # Use mod_xsendfile with the X-Sendfile header
            $message = "Sending file '$file' with X-Sendfile";
            $this->headers['X-Sendfile'] = $file;
            $this->send_headers();
            $status = true;
         } else {
            # Output the file normally
            $message = "Sending file '$file'";
            $this->send_headers();
            $status = readfile($file);
         }

         if ($options['inline']) { $message .= " inline"; }
         if ($name) { $message .= " as '$name'"; }
         if ($type) { $message .= " with type '$type'"; }
         log_info($message);

         $this->render_text('');
         return $status;
      }

      # Add invalid fields and an error message
      function add_error($keys, $message) {
         $this->_errors = array_unique(array_merge((array) $this->_errors, (array) $keys));
         $this->msg['error'] = array_merge((array) $this->msg['error'], (array) $message);
      }

      # Check if a form value has errors
      function has_errors($key) {
         if (substr($key, -2) == '[]') {
            $key = substr($key, 0, -2);
         }

         return in_array($key, (array) $this->_errors);
      }

      # Get error messages from model objects assigned to the template
      function set_model_errors() {
         $messages = array();
         foreach ($this->_view->data as $value) {
            if ($value instanceof Model) {
               foreach ($value->errors as $value) {
                  $messages = array_merge($messages, (array) $value);
               }
            }
         }

         if (!empty($messages)) {
            $this->msg['error'] = array_unique($messages);
         }
      }

      # Scaffold default model actions
      function scaffold($action='index') {
         $options = (array) $this->_scaffold_options;
         $args = array_slice(func_get_args(), 1);
         if (is_array($args[count($args) - 1])) {
            $options = array_merge(
               $options, array_pop($args)
            );
         }

         if (!$model = classify(any($options['model'], $this->_scaffold, pluralize($this->_name)))) {
            throw new ApplicationError("No model found");
         } elseif (!is_subclass_of($model, ActiveRecord)) {
            throw new TypeError("Invalid model '$model'");
         }

         $id = intval($args[0]);
         $params = $this->params[underscore($model)];

         if (method_exists($this, $method = "scaffold_$action")) {
            $status = call_user_func(array($this, $method),
               $model, &$options, $id, $params
            );
         } else {
            throw new ValueError("Invalid action '$action'");
         }

         $object = array_delete($status, 'object');
         $redirect = array_delete($status, 'redirect');

         foreach ((array) $status as $key => $message) {
            if (is_array($message)) {
               $args = array_slice($message, 1);
               array_unshift($args, humanize($model));
               array_unshift($args, $message[0]);
               $message = call_user_func_array('sprintf', $args);
            } else {
               $message = sprintf($message, humanize($model));
            }

            $this->msg[$key] = any(
               $options[$key], $options['message'], $message
            );
         }

         if ($redirect) {
            if ($this->is_ajax) {
               $this->head(200);
            } else {
               $redirect = any($options['redirect_to_action'], $redirect);

               if (!$this->has_action($redirect)) {
                  $redirect = '';
               }

               $id = ($redirect == 'show' or $redirect == 'edit') ? "/$id" : '';

               $this->redirect_to(any(
                  $options['redirect_to'],
                  ":{$options['prefix']}/$redirect$id"
               ));
            }
         } else {
            if ($this->is_post and $this->is_ajax) {
               $this->headers['Status'] = 500;
            }

            $this->set('model', underscore($model));
            $this->set('options', $options);
            $this->set('prefix', $options['prefix']);

            $attributes = DB($model)->attributes;
            if ($hide = $options['hide_attributes']) {
               array_delete($attributes, $hide);
            }
            $this->set('attributes', $attributes);

            $this->render(any($options['template'], array(
               "{$this->name}/$action",
               $options['default_template'],
               "scaffold/$action"
            )));
         }
      }

      protected function scaffold_assign($object) {
         $this->set($object);
         $this->set($object instanceof QuerySet ? 'objects' : 'object', $object);
      }

      protected function scaffold_index($model, $options) {
         $objects = DB($model)->sorted;

         if ($paginate = $options['paginate']) {
            if (is_numeric($paginate)) {
               DB($model)->page_size = $paginate;
            }
            $objects->paginated;
         }

         $this->scaffold_assign($objects);

         return array(
            'object' => $objects,
         );
      }

      protected function scaffold_show($model, $options, $id) {
         if ($object = DB($model)->find($id)) {
            $this->scaffold_assign($object);
         } else {
            throw new NotFound();
         }
      }

      protected function scaffold_create($model, $options, $id, $params) {
         $object = new $model($params);
         $this->scaffold_assign($object);

         $options['hide_attributes'] = array_merge(
            (array) $options['hide_attributes'],
            array(
               'created_at',
               'updated_at',
               DB($model)->primary_key,
            )
         );

         if ($this->is_post() and $object->save()) {
            return array(
               'object'   => $object,
               'info'     => array(_("%s '%s' successfully created"), $object),
               'redirect' => 'show',
            );
         } else {
            return array(
               'object' => $object,
            );
         }
      }

      protected function scaffold_edit($model, $options, $id, $params) {
         if ($object = DB($model)->find($id)) {
            $this->scaffold_assign($object);

            if ($this->is_post() and $object->update($params)) {
               return array(
                  'object'   => $object,
                  'info'     => array(_("%s '%s' successfully updated"), $object),
                  'redirect' => 'show',
               );
            } else {
               return array(
                  'object' => $object,
               );
            }
         } else {
            return array(
               'error'    => array(_("Couldn't find %s #%d"), $id),
               'redirect' => 'index',
            );
         }
      }

      protected function scaffold_destroy($model, $options, $id) {
         if (!$this->is_post()) {
            throw new InvalidRequest('needs POST');
         }

         if ($object = DB($model)->find($id)) {
            try {
               $object->destroy();
               return array(
                  'object'   => $object,
                  'info'     => array(_("%s '%s' successfully deleted"), $object),
                  'redirect' => 'index',
               );
            } catch (PDOException $e) {
               return array(
                  'object'   => $object,
                  'error'    => array(_("Couldn't delete %s '%s'"), $object),
                  'redirect' => 'index',
               );
            }
         } else {
            return array(
               'error'    => array(_("Couldn't find %s #%d"), $id),
               'redirect' => 'index',
            );
         }
      }
   }

?>
