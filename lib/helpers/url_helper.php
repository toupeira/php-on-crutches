<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function current_params(array $params=null) {
      $params = array_merge((array) Dispatcher::$params, (array) $params);
      foreach ($params as $key => $value) {
         if ($key[0] == '_') {
            unset($params[$key]);
         }
      }

      return $params;
   }

   # Build a URL for the given options
   function url_for($path, array $options=null) {
      if (is_null($path)) {
         return;

      } elseif (is_array($path)) {
         # Generate path from route parameters
         $path = Router::generate($path);

      } elseif (!is_string($path)) {
         throw new TypeError($path);

      } elseif ($path[0] == ':') {
         # Generate path from route string
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

      } elseif (preg_match('#^(\w+://|mailto:).#', $path)) {
         # Return fully-qualified URLs unchanged
         return $path;
      }

      # Build a fully-qualified URL
      if ($options['full'] or $options['ssl']) {
         unset($options['full']);

         #if ($path and $path[0] != '/') {
            # If the path is relative, use the directory name from the currently requested URI
            #$path = substr(dirname($_SERVER['REQUEST_URI']), 1)."/$path";
         #}

         # Use HTTPS if specified or the current site is already HTTPS
         if ($options['ssl']
            or (Dispatcher::$controller and Dispatcher::$controller->is_ssl())) {
            unset($options['ssl']);
            $url = 'https';
         } else {
            $url = 'http';
         }
         $url .= '://'.$_SERVER['HTTP_HOST'];
      } 

      # Add the site prefix for relative paths
      if ($path[0] != '/' and $path[0] != '#') {
         $url .= Dispatcher::$prefix;
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
   function link_to($title, $path, array $options=null, array $link_options=null) {
      $confirm = add_confirm_options($options);

      # Send a POST request by dyamically building a form element
      if ($options['post'] and $path != '#') {
         unset($options['post']);
         $options['onclick'] = "var f = document.createElement('form');"
                             . "f.style.display = 'none'; this.parentNode.appendChild(f);"
                             . "f.method = 'POST'; f.action = this.href; f.submit()";

         if ($confirm) {
            # Wrap in confirmation if requested
            $options['onclick'] = "if (confirm('$confirm')) { {$options['onclick']}; }";
         }

         $options['onclick'] .= "; return false";
      }

      return content_tag('a', $title, $options, array('href' => url_for($path, $link_options)));
   }

   # Build a link button
   function button_to($title, $path, array $options=null, array $link_options=null) {
      add_confirm_options(&$options);

      $method = any($options['post'] ? 'POST' : null, $options['method'], 'GET');
      unset($options['post']);
      unset($options['method']);

      $path = url_for($path, $link_options);

      if ($method == 'GET') {
         $options['onclick'] = "location.href = '$path'; return false";
      }

      return form_tag($path, array('method' => $method))
           . submit_button($title, $options) . "</form>\n";
   }

   # Add necessary options for confirmation, used in link_to() and button_to()
   function add_confirm_options(array &$options=null) {
      $confirm = $options['confirm'];
      unset($options['confirm']);

      if ($confirm) {
         $message = ($confirm === true ? _("Are you sure?") : $confirm);
         $options['onclick'] = "return confirm('$message')";
         return $message;
      }
   }

?>
