<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build a URL for the given options
   function url_for($path, $options=null) {
      if (array_delete($options, 'full_path') or $options['ssl']) {
         if (array_delete($options, 'ssl') or Dispatcher::$controller->is_ssl()) {
            $url = 'https';
         } else {
            $url = 'http';
         }
         $url .= '://'.$_SERVER['HTTP_HOST'];
      }

      $url .= Dispatcher::$prefix;

      if (is_array($path)) {
         # Generate path from route parameters
         $path = Router::generate($path);
      } elseif (preg_match('#^(/|\w+://)#', $path)) {
         # Return absolute paths and fully-qualified URIs unchanged
         return $path;
      } elseif ($path[0] == ':') {
         # Generate path from route template
         list($controller, $action, $id) = explode('/', $path, 3);
         $params = array();

         if ($controller == ':') {
            $params['controller'] = Dispatcher::$params['controller'];
         } else {
            $params['controller'] = substr($controller, 1);
         }

         if ($action == ':') {
            $params['action'] = Dispatcher::$params['action'];
         } else {
            $params['action'] = $action;
         }

         if ($id == ':') {
            $params['id'] = Dispatcher::$params['id'];
         } else {
            $params['id'] = $id;
         }

         $path = Router::generate($params);

      } elseif (!is_string($path)) {
         $type = gettype($path);
         raise("Invalid argument of type '$path'");
      }

      if ($anchor = $options['anchor']) {
         $path .= "#$anchor";
      }

      if (config('rewrite_urls')) {
         $url .= $path;
      } else {
         # Append the path to the query string if URL
         # rewriting is disabled.
         $url .= 'index.php?path='.str_replace('?', '&', $path);
      }

      return $url;
   }

   # Build a link tag
   function link_to($title, $path, $options=null, $link_options=null) {
      return content_tag('a', $title, $options, array('href' => url_for($path, $link_options)));
   }

   # Build a link button
   function button_to($title, $path, $options=null) {
      $method = $options['method'];
      unset($options['method']);

      if ($options['confirm']) {
         unset($options['confirm']);
         $options['onclick'] = "return confirm('Are you sure?')";
      }

      return form_tag($path, array('method' => any($method, 'get')))
           . submit_button($title, $options) . "</form>\n";
   }

?>
