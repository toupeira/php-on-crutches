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
      protected $_layout;
      protected $_view;
      protected $_output;
      protected $_action;

      protected $_actions;
      protected $_errors;

      protected $_require_post;
      protected $_require_ajax;
      protected $_require_ssl;
      protected $_require_trusted;

      function __construct(array &$params=null) {
         $this->_name = underscore(substr(get_class($this), 0, -10));

         # Load controller helper
         safe_require(HELPERS."{$this->_name}_helper.php");

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
         $this->_view = new View();

         # Standard variables for the view
         $this->set('controller', $this->_name);
         $this->set('params', &$this->params);
         $this->set('cookies', &$this->cookies);
         $this->set('msg', &$this->msg);

         # Collect all public methods defined in this controller
         $this->_actions = array_diff(
            get_class_methods($this),
            get_class_methods(Controller),
            get_class_methods(ApplicationController)
         );

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
         return (string) $this->_layout;
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
         if (
            # Check for POST requirements
            (!$this->is_post() and
               ($this->_require_post === true or
                  in_array($action, (array) $this->_require_post))) or
            # Check for Ajax requirements
            (!$this->is_ajax() and
               ($this->_require_ajax === true or
                  in_array($action, (array) $this->_require_ajax))) or
            # Check for trusted host requirements
            ((($hosts = config('trusted_hosts') and
               ($this->_require_trusted === true or
                  # Use default hosts
                  in_array($action, (array) $this->_require_trusted))) or
                     # Use specified hosts
                     ($hosts = $this->_require_trusted[$action]) or
                        ($hosts = $this->_require_trusted['all'])) and
                           !in_array($_SERVER['REMOTE_ADDR'], (array) $hosts))
         ) {
            if (ENVIRONMENT == 'development') {
               throw new ApplicationError('Invalid request for this action');
            } else {
               log_warn("Invalid request for this action");
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

            # Set the layout, don't use one for Ajax requests
            if ($this->is_ajax()) {
               $this->_view->layout = null;
            } elseif (!is_null($this->_layout)) {
               $this->_view->layout = $this->_layout;
            } else {
               $this->_view->layout = 'application';
            }

            # Call before filters
            $this->call_filter("global_before", $action);
            $this->call_filter("before", $action);
            $this->call_filter("before_$action");

            # Call the action itself if it's defined
            if (in_array($action, $this->_actions) and !method_exists(get_parent_class($this), $action)) {
               call_user_func_array(array($this, $action), (array) $args);
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
            if (strstr($action, '/') === false) {
               $template = $this->_name.'/'.$action;
            } else {
               $template = $action;
            }

            $this->_view->template = $template;
            if (!is_null($layout)) {
               $this->_view->layout = $layout;
            }
            $this->set_model_errors();
            $this->_output = $this->_view->render();
            return true;
         } else {
            throw new ApplicationError("Can only render once per request");
         }
      }

      # Render only the given text without layout
      function render_text($text) {
         $this->_output = $text;
         return true;
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
         $args = array(
            $name,
            $value,
            $options['expire'],
            any($options['path'], '/'),
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
   }

?>
