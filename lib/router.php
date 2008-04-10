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
         $type = gettype($route);
         throw new ApplicationError("Invalid argument of type '$type'");
      }
   }

   abstract class Router
   {
      static protected $routes = array();

      # List the configured routes
      static function routes() {
         $routes = array();
         foreach (self::$routes as $route) {
            $routes[$route->route] = $route->pattern;
         }
         return $routes;
      }

      # Add a new route
      static function add($route, $options=null) {
         if (is_array($route)) {
            foreach ($route as $route => $options) {
               self::add($route, $options);
            }
         } else {
            self::$routes[] = new Route($route, (array) $options);
         }
      }

      # Remove all configured routes
      static function clear() {
         self::$routes = array();
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
         foreach (self::$routes as $route) {
            if (!is_null($values = $route->recognize($path, $defaults))) {
               return $values;
            }
         }

         throw new RoutingError("Recognition failed for '$path'");
      }

      # Generate a URL from the given values
      static function generate($values) {
         foreach (self::$routes as $route) {
            if (!is_null($path = $route->generate($values))) {
               return $path;
            }
         }

         $values = array_to_str($values);
         throw new RoutingError("Failed to generate route from '$values'");
      }
   }

   class Route
   {
      public $route = '';
      public $pattern = '';

      protected $params = array();
      protected $defaults = array();
      protected $fixed = array();
      protected $required = array();
      protected $formats = array();

      function __construct($route, $defaults=null) {
         $this->route = $route;

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
               $this->pattern .= "/?$part";
            }

            # Add keys and pattern
            if ($key and $pattern) {
               # Check if parameter is required
               if (substr($key, -1) == '!') {
                  $key = substr($key, 0, -1);
                  $this->required[] = $key;
                  $pattern = rtrim($pattern, '?');
               }

               if (ctype_alpha($key)) {
                  $this->params[$key] = $part;
                  $this->pattern .= $pattern;
               } else{
                  throw new ApplicationError("Invalid parameter '$key'");
               }
            }
         }

         # Set default values
         if (!in_array('action', $this->required)) {
            $this->defaults['controller'] = '';
            $this->defaults['action'] = 'index';
         }

         # Get default/fixed arguments and format specifications
         foreach ((array) $defaults as $key => $value) {
            if ($value[0] == '/' and substr($value, -1) == '/') {
               # Use as format specification
               $this->formats[$key] = '/^'.substr($value, 1, -1).'$/';
            } else {
               # Use as default value
               $this->defaults[$key] = $value;
               if (!isset($this->params[$key])) {
                  $this->fixed[$key] = $value;
               }
            }
         }
      }

      function __toString() {
         return (string) $this->route;
      }

      # Check if the path matches this route
      function recognize($path, $defaults=null) {
         $values = array_merge($this->defaults, (array) $defaults);

         # Get parameter values
         if (preg_match("#^{$this->pattern}$#", $path, $match)) {
            $i = 1;
            foreach ($this->params as $key => $symbol) {
               $value = $match[$i];

               if ($format = $this->formats[$key] and !preg_match($format, $value)) {
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
      function generate($values) {
         $route = $this->route;

         # Remove fixed values, abort if they don't match this route
         foreach ($this->fixed as $key => $value) {
            if ($values[$key] != $value) {
               return;
            } else {
               unset($values[$key]);
            }
         }

         # Check for required values
         foreach ($this->required as $key) {
            if (!isset($values[$key])) {
               return;
            }
         }

         # Check for format specifications
         foreach ($this->formats as $key => $format) {
            if ($value = $values[$key] and !preg_match($format, $value)) {
               return;
            }
         }

         # Apply default values
         foreach ($this->defaults as $key => $value) {
            if (!isset($values[$key]) and isset($this->params[$key])) {
               $values[$key] = $value;
            }
         }

         # Build the route
         $add = false;
         foreach (array_reverse($this->params) as $key => $symbol) {
            $value = $values[$key];
            unset($values[$key]);

            if ($add or ($value and $value != $this->defaults[$key])) {
               $route = str_replace($symbol, $value, $route);
               $add = true;
            }
         }

         # Add remaining parameters to query string
         if ($values) {
            $query = array();
            foreach ($values as $key => $value) {
               if (!is_null($value)) {
                  $query[] = urlencode($key).'='.urlencode($value);
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
