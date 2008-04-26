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
      public $require_post;
      public $require_ajax;
      public $require_ssl;

      protected $name;
      protected $layout;
      protected $view;
      protected $output;
      protected $action;

      protected $params;
      protected $session;
      protected $headers;
      protected $cookies;
      protected $files;

      protected $methods;
      protected $actions;
      protected $errors;
      protected $msg;

      function __construct(&$params=null) {
         $this->name = underscore(substr(get_class($this), 0, -10));

         # Load controller helper
         safe_require(HELPERS."{$this->name}_helper.php");

         # Shortcuts for request data
         $this->params = &$params;
         $this->session = &$_SESSION;
         $this->cookies = &$_COOKIE;
         $this->files = &$_FILES;

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
         $this->view = new View();

         # Standard variables for the view
         $this->set('controller', $this->name);
         $this->set('params', &$this->params);
         $this->set('cookies', &$this->cookies);
         $this->set('msg', &$this->msg);

         # Collect all public methods defined in this controller
         $this->actions = array_diff(
            get_class_methods($this),
            get_class_methods(Controller),
            get_class_methods(ApplicationController)
         );

         # Call custom initializer
         $this->call_if_defined('init');
      }

      function get_name() {
         return (string) $this->name;
      }

      function get_layout() {
         return (string) $this->layout;
      }

      function get_output() {
         return (string) $this->output;
      }

      function get_view() {
         return $this->view;
      }

      function get_params() {
         return (array) $this->params;
      }

      function get_session() {
         return (array) $this->session;
      }

      function get_headers() {
         return (array) $this->headers;
      }

      function get_cookies() {
         return (array) $this->cookies;
      }

      function get_files() {
         return (array) $this->files;
      }

      function get_actions() {
         return (array) $this->actions;
      }

      function get_errors() {
         return (array) $this->errors;
      }

      function get_msg() {
         return (array) $this->msg;
      }

      # Get and set template values
      function get($key) {
         return $this->view->get($key);
      }

      function set($key, $value) {
         $this->view->set($key, &$value);
         return $this;
      }

      # Check if this is a POST request
      function is_post() {
         return $_SERVER['REQUEST_METHOD'] == 'POST';
      }

      # Check if this is an Ajax request
      function is_ajax() {
         return strstr($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest');
      }

      # Check if SSL is enabled
      function is_ssl() {
         return $_SERVER['HTTPS'] != '' and $_SERVER['HTTPS'] != 'off';
      }

      # Check request requirements
      function is_valid_request($action) {
         # Check POST and Ajax requirements
         if (
            (!$this->is_post() and
               ($this->require_post === true or
                  in_array($action, (array) $this->require_post))) or
            (!$this->is_ajax() and
               ($this->require_ajax === true or
                  in_array($action, (array) $this->require_ajax)))
         ) {
            if (config('debug')) {
               throw new ApplicationError('Invalid request for this action');
            } else {
               log_warn("Invalid request for this action");
               if ($action == 'index') {
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
         if (!$this->is_ssl() and ($this->require_ssl === true or
                                   in_array($action, (array) $this->require_ssl))) {
            $this->redirect_to("https://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}");
            return false;
         }

         return true;
      }

      # Perform an action
      function perform($action, $args=null) {
         # Catch invalid action names
         if (!preg_match('/^[a-z][a-z_]*$/', $action) or $action == 'init'
            or substr($action, 0, 6) == 'before'
            or substr($action, 0, 5) == 'after') {
            throw new RoutingError("Invalid action '$action'");
         }

         if ($this->is_valid_request($action)) {
            $this->action = $action;
            $this->set('action', $action);

            # Set the layout, don't use one for Ajax requests
            if ($this->is_ajax()) {
               $this->view->layout = null;
            } elseif (!is_null($this->layout)) {
               $this->view->layout = $this->layout;
            } else {
               $this->view->layout = 'application';
            }

            # Call before filters
            $this->call_filter("global_before");
            $this->call_filter("before");
            $this->call_filter("before_$action");

            # Call the action itself if it's defined
            if (in_array($action, $this->actions)) {
               call_user_func_array(array($this, $action), (array) $args);
            }

            # Call after filters
            $this->call_filter("after_$action");
            $this->call_filter("after");
            $this->call_filter("global_after");

            # Render the action template if the action didn't generate any output
            if ($this->output === null) {
               $this->render($action);
            }
         }

         $this->send_headers();
         return $this->output;
      }

      # Render an action
      function render($action, $layout=null) {
         if ($this->output === null) {
            if (strstr($action, '/') === false) {
               $template = $this->name.'/'.$action;
            } else {
               $template = $action;
            }

            $this->view->template = $template;
            if (!is_null($layout)) {
               $this->view->layout = $layout;
            }
            $this->set_model_errors();
            $this->output = $this->view->render();
            return true;
         } else {
            throw new ApplicationError("Can only render once per request");
         }
      }

      # Render only the given text without layout
      function render_text($text) {
         $this->output = $text;
         return true;
      }

      # Redirect to a path
      function redirect_to($path, $code=302) {
         $url = url_for($path, array('full' => true));

         # Save messages so they can be displayed in the next request
         $this->set_model_errors();
         $this->session['msg'] = $this->msg;

         log_debug("Redirecting to $url");

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

      # Send the configured headers
      function send_headers() {
         foreach ((array) $this->headers as $header => $value) {
            if ($value !== null) {
               $header = ($header == 'Status' ? "HTTP/1.x $value" : "$header: $value");
               if (defined('TESTING')) {
                  # Ignore errors when testing
                  @header($header);
               } else {
                  header($header);
               }
            }
         }

         return true;
      }

      # Send a cookie
      function send_cookie($name, $value, $options=null) {
         $args = array(
            $name,
            $value,
            $options['expire'],
            any($options['path'], '/'),
            $options['domain'],
            $options['secure'],
            $options['httponly'],
         );

         if (defined('TESTING')) {
            # Ignore errors when testing
            return @call_user_func_array(setcookie, $args);
         } else {
            return call_user_func_array(setcookie, $args);
         }
      }

      # Delete a cookie
      function delete_cookie($name, $options=null) {
         return $this->send_cookie($name, '', array_merge(
            (array) $options, array('expire' => time() - 3600)
         ));
      }

      # Send a file with the appropriate headers
      function send_file($file, $options=null) {
         unset($this->headers['Content-Type']);

         if ($file[0] == '!') {
            $command = substr($file, 1);
            $file = null;
         } elseif (!is_file($file)) {
            throw new NotFound("File $file not found");
         }

         if (!$options['inline']) {
            $this->headers['Content-Disposition'] = 'attachment';

            if (ctype_print($name = $options['name'])) {
               $name = str_replace('"', '\"', $name);
               $this->headers['Content-Disposition'] .= "; filename=\"$name\"";
            }
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
            log_debug("Sending output of '$command'");
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
         $this->errors = array_unique(array_merge((array) $this->errors, (array) $keys));
         $this->msg['error'] = array_merge((array) $this->msg['error'], (array) $message);
      }

      # Check if a form value has errors
      function has_errors($key) {
         if (substr($key, -2) == '[]') {
            return in_array(substr($key, 0, -2), (array) $this->errors);
         } else {
            return in_array($key, (array) $this->errors);
         }
      }

      # Get error messages from model objects assigned to the template
      function set_model_errors() {
         $messages = array();
         foreach ($this->view->data as $key => $value) {
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
   }

?>
