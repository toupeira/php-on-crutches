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

      # Extract route values from a path
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
            if (!is_null($values = $route->recognize($path, $defaults))) {
               return $values;
            }
         }

         throw new RoutingError("Recognition failed for '$path'");
      }

      # Generate a URL from the given values
      static function generate(array $values) {
         foreach (self::$_routes as $route) {
            if (!is_null($path = $route->generate($values))) {
               return $path;
            }
         }

         $values = array_to_str($values);
         throw new RoutingError("Failed to generate route from '$values'");
      }
   }

   class Route extends Object
   {
      protected $_route = '';
      protected $_pattern = '';

      protected $_params = array();
      protected $_defaults = array();
      protected $_fixed = array();
      protected $_required = array();
      protected $_formats = array();

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

         # Set default values
         if (!in_array('action', $this->_required)) {
            $this->_defaults['controller'] = '';
            $this->_defaults['action'] = 'index';
         }

         # Get default/fixed arguments and format specifications
         foreach ((array) $defaults as $key => $value) {
            if ($value[0] == '/' and substr($value, -1) == '/') {
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

      # Check if the path matches this route
      function recognize($path, array $defaults=null) {
         $values = array_merge($this->_defaults, (array) $defaults);

         # Get parameter values
         if (preg_match("#^{$this->_pattern}$#", $path, $match)) {
            $i = 1;
            foreach ($this->_params as $key => $symbol) {
               $value = $match[$i];

               if ($format = $this->_formats[$key] and !preg_match($format, $value)) {
                  # Check for format specification
                  return;
               } elseif ($value) {
                  # Add value if not empty
                  $values[$key] = $value;
               }

               $i++;
            }

            return $values;
         }
      }

      # Generate a path from the given values
      function generate(array $values) {
         $route = $this->_route;

         # Remove fixed values, abort if they don't match this route
         foreach ($this->_fixed as $key => $value) {
            if ($values[$key] != $value) {
               return;
            } else {
               unset($values[$key]);
            }
         }

         # Check for required values
         foreach ($this->_required as $key) {
            if (!isset($values[$key])) {
               return;
            }
         }

         # Check for format specifications
         foreach ($this->_formats as $key => $format) {
            if ($value = $values[$key] and !preg_match($format, $value)) {
               return;
            }
         }

         # Apply default values
         foreach ($this->_defaults as $key => $value) {
            if (!isset($values[$key]) and isset($this->_params[$key])) {
               $values[$key] = $value;
            }
         }

         # Build the route
         $add = false;
         foreach (array_reverse($this->_params) as $key => $symbol) {
            $value = $values[$key];
            unset($values[$key]);

            if ($add or ($value and $value != $this->_defaults[$key])) {
               $route = str_replace($symbol, $value, $route);
               $add = true;
            }
         }

         # Add remaining parameters to query string
         if ($values) {
            $query = array();

            foreach ($values as $key => $value) {
               if (!is_null($value)) {
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
