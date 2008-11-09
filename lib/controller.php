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

      protected $_scaffold;
      protected $_scaffold_actions = array(
         'index',
         'show',
         'create',
         'edit',
         'destroy',
      );

      function __construct(array &$params=null) {
         $this->_name = underscore(substr(get_class($this), 0, -10));

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
               unset($this->files[$i]);
            }
         }

         # Set default headers
         $this->headers = array(
            'Content-Type' => 'text/html; charset=utf-8',
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
         $this->set('params', &$this->params);
         $this->set('cookies', &$this->cookies);
         $this->set('msg', &$this->msg);

         # Collect all public methods defined in this controller
         $this->_actions = array();
         $class = new ReflectionClass($this);
         foreach ($class->getMethods() as $method) {
            $name = $method->getName();
            if ($method->isPublic() and !method_exists(Controller, $name)
                                    and !method_exists(ApplicationController, $name)) {
               $this->_actions[] = $name;
            }
         }

         # Call custom initializer
         $this->call_if_defined('init');
      }

      function __toString() {
         return parent::__toString($this->params);
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

      function set($key, $value) {
         $this->_view->set($key, &$value);
         return $this;
      }

      function set_default($key, $value) {
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
             or array_key_exists($action, (array) $requirement);
      }

      # Check request requirements
      function is_valid_request($action) {
         # Check for POST requirements
         if (!$this->is_post() and $this->check_requirement($action, 'post')) {
            $error = 'needs POST';
         }


         # Check for Ajax requirements
         if (!$this->is_ajax() and $this->check_requirement($action, 'ajax')) {
            $error = 'needs Ajax';
         }

         # Check for trusted host requirements
         if ($this->check_requirement($action, 'trusted')) {
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
            foreach ($hosts as $host) {
               $host = strtr($host, array(
                  '*' => '[0-9]+',
                  '.' => '\\.',
               ));
               if (preg_match("/^$host$/", $client)) {
                  $found = true;
                  break;
               }
            }

            if (!$found) {
               $error = "untrusted host $client";
            }
         }

         # Check for cross site request forgery
         if ($this->is_post() and config('form_token') and $this->check_requirement($action, 'form_token')) {
            if (!$_POST['_form_token']) {
               $error = 'missing form token';
            } elseif ($_POST['_form_token'] != $this->session['form_token']) {
               $error = 'forged form token';
            } elseif ($max_time = config('form_token_time') and $form_time = $this->session['form_token_time']) {
               if (time() - $form_time > $max_time) {
                  $error = 'expired form token';
               }
            }
         }

         if ($error) {
            if (config('debug')) {
               throw new InvalidRequest($error);
            } else {
               log_warn($message);
               if ($action == 'index' or !method_exists($this, 'index')) {
                  # Redirect to default path if the default action was requested
                  $this->redirect_to('/');
               } else {
                  # Or else try the default action
                  $this->redirect_to(':');
               }
            }

            return false;
         }

         # Check SSL requirements
         if (!$this->is_ssl() and
               ($this->_require_ssl === true or
                  in_array($action, (array) $this->_require_ssl)))
         {
            $this->redirect_to("https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
            return false;
         }

         return true;
      }

      # Perform an action
      function perform($action, $args=null) {
         # Catch invalid action names
         if ($action == 'init' or
               substr($action, 0, 6) == 'before' or substr($action, 0, 5) == 'after' or
                  !preg_match('/^[a-z][a-z_]*$/', $action) or
                     method_exists(get_parent_class($this), $action))
         {
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
            if (in_array($action, $this->_actions) and !method_exists(get_parent_class($this), $action)) {
               call_user_func_array(array($this, $action), (array) $args);
            } elseif ($model = $this->_scaffold and in_array($action, $this->_scaffold_actions)) {
               array_unshift($args, $action == 'index' ? 'list' : $action);
               array_unshift($args, $model);
               call_user_func_array(array($this, scaffold), $args);
            }

            # Call after filters
            $this->call_filter("after_$action");
            $this->call_filter("after", $action);
            $this->call_filter("global_after", $action);

            # Render the action template if the action didn't generate any output
            if ($this->_output === null) {
               $this->render($action);
            }
         }

         $this->send_headers();
         return $this->_output;
      }

      # Render an action
      function render($action, $layout=null) {
         if ($this->_output === null) {
            if (is_array($action)) {
               $template = '{'.implode(',', $action).'}';
            } elseif (strstr($action, '/') === false) {
               $template = $this->_name.'/'.$action;
            } else {
               $template = $action;
            }

            $this->set_model_errors();

            return $this->_output = $this->_view->render(
               $template, (is_null($layout) ? $this->layout : $layout)
            );

         } else {
            throw new ApplicationError("Can only render once per request");
         }
      }

      function cache_render($key=null, $full=false) {
         $key = any($key, "view_".urlencode(Dispatcher::$path));
         $this->view->cache($key, $full);
         if (cache($key)) {
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

      # Redirect to a path
      function redirect_to($path, $code=302) {
         $url = url_for($path, array('full' => true));

         # Save messages so they can be displayed in the next request
         $this->set_model_errors();
         $this->session['msg'] = $this->msg;

         log_info("Redirecting to $url");

         if (config('debug_redirects')) {
            $this->render_text("Redirecting to ".link_to(h($url), $url));
         } else {
            $this->headers['Location'] = $url;
            $this->headers['Status'] = $code;
            $this->render_text(' ');
         }

         return true;
      }

      # Redirect to the previous page
      function redirect_back($default=null) {
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

         return $this->redirect_to($path);
      }

      # Start the session if necessary
      function start_session() {
         if (!session_id() and PHP_SAPI != 'cli') {
            session_start();
            log_info('  Session ID: '.session_id());

            # Override default no-cache headers
            header('Cache-Control: private');
            header('Pragma: cache');

            # Make sure the session handler can clean up
            register_shutdown_function(session_write_close);
         }
      }

      # Send the configured headers
      function send_headers() {
         foreach ((array) $this->headers as $header => $value) {
            if ($value !== null) {
               $header = ($header == 'Status' ? "HTTP/1.x $value" : "$header: $value");
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
            return @call_user_func_array(setcookie, $args);
         } else {
            return call_user_func_array(setcookie, $args);
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

         $this->send_headers();
         $this->render_text('');

         if ($command) {
            log_info("Sending output of '$command'");
            passthru("$command 2>/dev/null");
            return true;
         } elseif (@readfile($file)) {
            return true;
         } else {
            throw new ApplicationError("Can't read file $file");
         }
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
         foreach ($this->_view->data as $key => $value) {
            if ($value instanceof Model) {
               foreach ($value->errors as $key => $value) {
                  $messages = array_merge($messages, (array) $value);
               }
            }
         }

         if (!empty($messages)) {
            $this->msg['error'] = $messages;
         }
      }

      # Scaffold default model actions
      function scaffold($model, $action='list') {
         if (is_subclass_of($model, ActiveRecord)) {
            $args = array_slice(func_get_args(), 2);
            if (is_array($args[count($args) - 1])) {
               $options = array_pop($args);
               $this->set('options', $options);
            }

            $db = DB($model);
            $model_key = underscore($model);
            $this->set('model', $model_key);
            $attributes = $db->attributes;

            # Use a path prefix for all redirects
            $prefix = $options['path_prefix'];
            $this->set('prefix', $prefix);

            switch ($action) {
               case 'list':
                  if ($page_size = $options['page_size']) {
                     $db->page_size = $page_size;
                  }

                  $this->set('objects', $db->sorted->paginated);
                  break;
               case 'show':
                  if ($object = $db->find((int) $args[0])) {
                     $this->set('object', $object);
                  } else {
                     throw new NotFound();
                  }
                  break;
               case 'create':
                  $object = new $model($this->params[$model_key]);
                  $this->set('object', $object);
                  array_delete($attributes, array('created_at', 'updated_at', $db->primary_key));

                  if ($this->is_post() and $object->save()) {
                     $this->msg['info'] = any(
                        $options['message'],
                        sprintf(_("%s successfully created"), $model)
                     );
                     return $this->redirect_to(any($options['redirect_to'], ":$prefix/show/{$object->id}"));
                  }
                  break;
               case 'edit':
                  if ($object = $db->find((int) $args[0])) {
                     $this->set('object', $object);

                     if ($this->is_post() and $object->update($this->params[$model_key])) {
                        $this->msg['info'] = any(
                           $options['message'],
                           sprintf(_("%s successfully updated"), $model)
                        );
                        return $this->redirect_to(any($options['redirect_to'], ":$prefix/show/{$object->id}"));
                     }
                  } else {
                     $this->msg['error'] = any(
                        $options['error'],
                        sprintf(_("Couldn't find %s #%d"), $model, $args[0])
                     );
                     return $this->redirect_to(any($options['redirect_to'], ":$prefix"));
                  }
                  break;
               case 'destroy':
                  if (!$this->is_post()) {
                     throw new InvalidRequest('needs POST');
                  }

                  if ($object = $db->find((int) $args[0])) {
                     try {
                        $object->destroy();
                        $this->msg['info'] = any(
                           $options['message'],
                           sprintf(_("%s successfully deleted"), $model)
                        );
                     } catch (PDOException $e) {
                        $this->msg['error'] = any(
                           $options['error'],
                           sprintf(_("Couldn't delete %s #%d"), $model, $args[0])
                        );
                     }
                  } else {
                     $this->msg['error'] = any(
                        $options['error'],
                        sprintf(_("Couldn't find %s #%d"), $model, $args[0])
                     );
                  }

                  return $this->redirect_to(any($options['redirect_to'], ":$prefix"));
                  break;
               default:
                  throw new ValueError("Invalid action '$action'");
            }

            $this->set('attributes', $attributes);
            $this->render(any($options['template'], array("{$this->name}/$action", "scaffold/$action")));

         } else {
            throw new TypeError("Invalid model '$model'");
         }
      }
   }

?>
