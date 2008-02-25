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
         return Route::recognize($route);
      } elseif (is_array($route)) {
         return Route::generate($route);
      } else {
         $type = gettype($route);
         raise("Invalid argument of type '$type'");
      }
   }

   class Route
   {
      static protected $routes = array();

      protected $pattern = '';
      protected $params = array();

      # Add a new route
      static function add($route, $defaults=null) {
         if (is_array($route)) {
            foreach ($route as $route => $defaults) {
               self::add($route, $defaults);
            }
         } else {
            self::$routes[] = new Route($route, (array) $defaults);
         }
      }

      # Remove all configured routes
      static function clear() {
         self::$routes = array();
      }

      # Extract route values from a path
      static function recognize($path) {
         foreach (self::$routes as $route) {
            if (!is_null($values = $route->recognize_route($path))) {
               return $values;
            }
         }
      }

      # Generate a URL from the given values
      static function generate($values) {
         foreach (self::$routes as $route) {
            if (!is_null($path = $route->generate_route($values))) {
               return $path;
            }
         }
      }

      function __construct($route, $defaults=null) {
         $this->route = $route;

         $parts = explode('/', trim($route, '/'));
         foreach ($parts as $i => $part) {
            if ($part[0] == ':') {
               $key = substr($part, 1);
               if (substr($key, -1) == '!') {
                  $key = substr($key, 0, -1);
                  $pattern = '([^/]+)/?';
               } else {
                  $pattern = '(?:([^/]+)/?)?';
               }

               if (ctype_alpha($key)) {
                  $this->params[] = $key;
                  $this->pattern .= $pattern;
               } else{
                  raise("Invalid parameter '$key'");
               }

            } elseif ($part[0] == '*') {
               $this->params[] = substr($part, 1);
               $this->pattern .= '(.*)';
               break;

            } else {
               $this->pattern .= "$part/?";
            }
         }

         foreach ((array) $defaults as $key => $value) {
            if (is_null($this->params[$key])) {
               $this->params[$key] = $value;
            }
         }
      }

      function __toString() {
         return (string) $this->route;
      }

      # Check if the path matches this route
      function recognize_route($path) {
         $values = $this->defaults;
         if (preg_match("#^{$this->pattern}$#", $path, $match)) {
            foreach ($this->params as $i => $key) {
               if ($value = $match[$i + 1]) {
                  $values[$key] = $value;
               }
            }

            return $values;
         }
      }

      # Generate a path from the given values
      function generate_route($values) {
         $route = $this->route;

         foreach ($values as $key => $value) {
            if (!is_null($default = $this->defaults[$key]) and $value != $default) {
               return null;
            } else {
               $route = preg_replace("#[:*]$key!?#", $value, $route);
            }
         }

         return preg_replace('#/?[:*][a-z_]+!?#', '', $route);
      }
   }

?>
