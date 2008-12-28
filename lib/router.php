<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function R($route) {
      if (is_string($route)) {
         return Router::recognize($route);
      } elseif (is_array($route)) {
         return Router::generate($route);
      } else {
         throw new TypeError($route);
      }
   }

   abstract class Router
   {
      static protected $_routes = array();

      # List the configured routes
      static function routes() {
         return self::$_routes;
      }

      # Add a new route
      static function add($route, $options=null) {
         if (is_array($route)) {
            foreach ($route as $route => $options) {
               self::add($route, $options);
            }
         } elseif (!is_null($route)) {
            self::$_routes[] = new Route($route, (array) $options);
         }
      }

      # Remove all configured routes
      static function clear() {
         self::$_routes = array();
      }

      # Extract route parameters from a path
      static function recognize($path) {
         $path = trim($path, '/');

         # Get query string parameters
         $defaults = array();
         if (preg_match('/^([^?]+)\?(.*)$/', $path, $match)) {
            $path = $match[1];
            if ($data = $match[2]) {
               foreach (explode('&', $data) as $data) {
                  list($key, $value) = explode('=', $data, 2);
                  if ($key and $value !== null) {
                     $defaults[urldecode($key)] = urldecode($value);
                  }
               }
            }
         }

         # Find matching route
         foreach (self::$_routes as $route) {
            if (!is_null($params = $route->recognize($path, $defaults))) {
               return $params;
            }
         }

         throw new RoutingError("Invalid path '$path'");
      }

      # Generate a URL from the given parameters
      static function generate($params) {
         if (is_object($params)) {
            if (method_exists($params, 'to_params')) {
               $params = $params->to_params;
            } else {
               throw new TypeError($params, "Missing to_params() method in class '%s'");
            }
         }

         foreach (self::$_routes as $route) {
            if (!is_null($path = $route->generate($params))) {
               return $path;
            }
         }

         $params = array_to_str($params);
         throw new RoutingError("Failed to generate route from '$params'");
      }
   }

   class Route extends Object
   {
      static protected $_request_filters = array(
         'accept',
         'accept_language',
         'cookie',
         'host',
         'user_agent',

         'remote_addr',
         'request_method',
         'request_uri',
         'server_addr',
         'server_port',
         'https',
      );

      protected $_route = '';
      protected $_pattern = '';

      protected $_params = array();
      protected $_defaults = array();
      protected $_fixed = array();
      protected $_required = array();
      protected $_formats = array();
      protected $_request = array();

      function __construct($route, array $defaults=null) {
         $this->_route = $route;

         $parts = explode('/', trim($route, '/'));
         foreach ($parts as $i => $part) {
            $key = $pattern = null;

            if ($part[0] == ':') {
               # Add substitution parameter
               $key = substr($part, 1);
               $pattern = '/?([^/]+)?';
            } elseif ($part[0] == '*') {
               # Add wildcard parameter
               $key = substr($part, 1);
               $pattern = '/?(.*)';
            } else {
               # Add literal text
               $this->_pattern .= "/?$part";
               continue;
            }

            # Add keys and pattern
            if ($key and $pattern) {
               # Check if parameter is required
               if (substr($key, -1) == '!') {
                  $key = substr($key, 0, -1);
                  $this->_required[] = $key;
                  $pattern = rtrim($pattern, '?');
               }

               if (ctype_alpha($key)) {
                  $this->_params[$key] = $part;
                  $this->_pattern .= $pattern;
               } else{
                  throw new ApplicationError("Invalid parameter '$key'");
               }
            }
         }

         # Set default action
         if (!$this->_params['action'] or !in_array('action', $this->_required)) {
            $this->_defaults['action'] = 'index';
         }

         # Get default/fixed arguments and format specifications
         foreach ((array) $defaults as $key => $value) {
            if ($this->is_request_filter($key)) {
               # Use as request filter
               $this->_request[$key] = $value;
            } elseif ($value[0] == '/' and substr($value, -1) == '/') {
               # Use as format specification
               $this->_formats[$key] = '/^'.substr($value, 1, -1).'$/';
            } else {
               # Use as default value
               $this->_defaults[$key] = $value;
               if (!isset($this->_params[$key])) {
                  $this->_fixed[$key] = $value;
               }
            }
         }

         if (!$this->_params['controller'] and !$this->_defaults['controller']) {
            throw new ApplicationError("Route doesn't specify a controller");
         }
      }

      function __toString() {
         return parent::__toString($this->_route);
      }

      function get_route() {
         return $this->_route;
      }

      function get_pattern() {
         return $this->_pattern;
      }

      function is_request_filter($key) {
         $key = strtolower($key);
         return in_array($key, self::$_request_filters)
             or in_array("http_$key", self::$_request_filters);
      }

      function get_request_value($key) {
         $key = strtoupper($key);
         if (array_key_exists($key, $_SERVER) or
             array_key_exists($key = "HTTP_$key", $_SERVER))
         {
            return $_SERVER[$key];
         }
      }

      # Check if the path matches this route
      function recognize($path, array $defaults=null) {
         if (preg_match("#^{$this->_pattern}$#", $path, $match)) {
            $params = array_merge($this->_defaults, (array) $defaults);

            # Check request filters
            foreach ($this->_request as $key => $pattern) {
               if (!match($pattern, $this->get_request_value($key))) {
                  return;
               }
            }

            $i = 1;
            foreach ($this->_params as $key => $symbol) {
               $value = $match[$i];

               if ($format = $this->_formats[$key] and !preg_match($format, $value)) {
                  # Check for format specification
                  return;
               } elseif ($value) {
                  # Add value if not empty
                  $params[$key] = $value;
               }

               $i++;
            }

            # Check if a controller and action was detected
            $controller = $params['controller'];
            if (!$controller or !$params['action']) {
               return;
            }

            # Check if the controller exists and is enabled
            $controller = $controller.'_controller';
            if (!$class = classify($controller)) {
               return;
            } elseif (config($controller) === false) {
               throw new RoutingError("$class is disabled");
            } elseif ($class = new ReflectionClass($class) and !$class->isInstantiable()) {
               return;
            }

            return $params;
         }
      }

      # Generate a path from the given parameters
      function generate(array $params) {
         $route = $this->_route;

         if ($controller = $params['controller'] and substr($controller, -10) == 'Controller') {
            $params['controller'] = underscore(substr($controller, 0, -10));
         }

         if (!$params['action']) {
            $params['action'] = 'index';
         }

         # Expand object parameters
         foreach ($params as $key => $value) {
            if (is_object($value)) {
               if (method_exists($value, 'to_param')) {
                  $params[$key] = $value->to_param;
               } else {
                  throw new TypeError($value, "Missing to_param() method in class '%s'");
               }
            }
         }

         # Remove fixed parameters, abort if they don't match this route
         foreach ($this->_fixed as $key => $value) {
            if ($params[$key] != $value) {
               return;
            } else {
               unset($params[$key]);
            }
         }

         # Check for required parameters
         foreach ($this->_required as $key) {
            if (!isset($params[$key])) {
               return;
            }
         }

         # Check for format specifications
         foreach ($this->_formats as $key => $format) {
            if ($value = $params[$key] and !preg_match($format, $value)) {
               return;
            }
         }

         # Apply default values
         foreach ($this->_defaults as $key => $value) {
            if (!isset($params[$key]) and isset($this->_params[$key])) {
               $params[$key] = $value;
            }
         }

         # Replace all route symbols
         $add = false;
         foreach (array_reverse($this->_params) as $key => $symbol) {
            $value = $params[$key];
            unset($params[$key]);

            if ($add or ($value and $value != $this->_defaults[$key])) {
               $route = str_replace($symbol, $value, $route);
               $add = true;
            }
         }

         # Add remaining parameters to query string
         if ($params) {
            $query = array();

            foreach ($params as $key => $value) {
               if (!is_null($value) and $value != $this->_defaults[$key]) {
                  if (is_numeric($key)) {
                     $query[] = urlencode($value);
                  } elseif (is_array($value)) {
                     $query[] = strtr(http_build_query(array($key => $value)), array(
                        '%5B' => '[', '%5D' => ']'
                     ));
                  } else {
                     $query[] = urlencode($key).'='.urlencode($value);
                  }
               }
            }

            if ($query) {
               $route .= '?'.implode('&', $query);
            }
         }

         return preg_replace('#/?[:*][a-z_]+!?#', '', $route);
      }
   }

?>
