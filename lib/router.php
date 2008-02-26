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
         raise("Invalid argument of type '$type'");
      }
   }

   abstract class Router
   {
      static protected $routes = array();

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
            #print "testing route {$route->route} with '$path'\n";
            if (!is_null($values = $route->recognize($path))) {
               #print "matched!\n";
               return $values;
            }
         }

         raise("Can't recognize route '$path'");
      }

      # Generate a URL from the given values
      static function generate($values) {
         foreach (self::$routes as $route) {
            #print "testing route {$route->route} with '".array_to_str($values)."'\n";
            if (!is_null($path = $route->generate($values))) {
               #print "matched!\n";
               return $path;
            }
         }

         $values = array_to_str($values);
         raise("Failed to generate route from '$values'");
      }
   }

   class Route
   {
      protected $pattern = '';

      protected $params = array();
      protected $defaults = array();
      protected $fixed = array();
      protected $required = array();

      function __construct($route, $defaults=null) {
         $this->route = $route;

         $parts = explode('/', trim($route, '/'));
         foreach ($parts as $i => $part) {
            if ($part[0] == ':') {
               # Add substitution parameter
               $key = substr($part, 1);
               if (substr($key, -1) == '!') {
                  $key = substr($key, 0, -1);
                  $this->required[] = $key;
                  $pattern = '([^/]+)/?';
               } else {
                  $pattern = '(?:([^/]+)/?)?';
               }

               if (ctype_alpha($key)) {
                  $this->params[$key] = $part;
                  $this->pattern .= $pattern;
               } else{
                  raise("Invalid parameter '$key'");
               }

            } elseif ($part[0] == '*') {
               # Add wildcard parameter
               $key = substr($part, 1);
               if (substr($key, -1) == '!') {
                  $key = substr($key, 0, -1);
                  $this->required[] = $key;
                  $pattern .= '(.+)';
               } else {
                  $pattern .= '(.*)';
               }

               if (ctype_alpha($key)) {
                  $this->params[$key] = $part;
                  $this->pattern .= $pattern;
                  break;
               } else{
                  raise("Invalid parameter '$key'");
               }

            } else {
               $this->pattern .= "$part/?";
            }
         }

         # Get default and fixed arguments
         foreach ((array) $defaults as $key => $value) {
            $this->defaults[$key] = $value;
            if (!isset($this->params[$key])) {
               $this->fixed[$key] = $value;
            }
         }
      }

      function __toString() {
         return (string) $this->route;
      }

      # Check if the path matches this route
      function recognize($path) {
         $values = $this->defaults;

         # Get query string parameters
         if (preg_match('/^([^?]+)\?(.*)$/', $path, $match)) {
            $path = $match[1];
            if ($data = $match[2]) {
               foreach (explode('&', $data) as $data) {
                  list($key, $value) = explode('=', $data, 2);
                  $values[urldecode($key)] = urldecode($value);
               }
            }
         }

         # Get parameter values
         if (preg_match("#^{$this->pattern}$#", $path, $match)) {
            $i = 1;
            foreach ($this->params as $key => $symbol) {
               if ($value = $match[$i]) {
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
            if (array_delete($values, $key) != $value) {
               return;
            }
         }

         # Check for required values
         foreach ($this->required as $key) {
            if (!isset($values[$key])) {
               return;
            }
         }

         /*
         # Set default values
         $last = false;
         foreach ($this->defaults as $key => $value) {
            if (isset($values[$key])) {
               if ($values[$key] == $value) {
                  unset($values[$key]);
                  $last = true;
               } else {
                  $last = false;
               }
            } elseif (!$last and isset($this->params[$key])) {
               $values[$key] = $value;
            }
         }
         */

         # Build the route
         #$additional = array();
         $last = false;
         foreach (array_reverse($this->params) as $key => $symbol) {
            print " :$key => ";
            $default = $this->defaults[$key];
            if ($value = array_delete($values, $key)) {
               if (!$last and $value == $default) {
                  print "skipping default value: $value";
                  $last = true;
               } elseif ($value or $value = $default) {
                  print "replacing value: $value";
                  $route = str_replace($symbol, $value, $route);
                  $last = false;
               }
            } elseif ($last and $default) {
               print "using default value";
               $route = str_replace($symbol, $default, $route);
               $last = false;
            }
            print "\n";
         }
         /*
         foreach ($values as $key => $value) {
            if ($symbol = $this->params[$key]) {
               $route = str_replace($symbol, $value, $route);
            } else {
               $additional[$key] = $value;
            }
         }
         */

         # Add remaining parameters to query string
         if ($values) {
            $query = array();
            foreach ($values as $key => $value) {
               $query[] = urlencode($key).'='.urlencode($value);
            }
            $route .= '?'.implode('&', $query);
         }

         return preg_replace('#/?[:*][a-z_]+!?#', '', $route);
      }
   }

?>
