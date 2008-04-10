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
      if (is_array($path)) {
         # Generate path from route parameters
         $path = Router::generate($path);
      } elseif (!is_string($path)) {
         $type = gettype($path);
         throw new ApplicationError("Invalid argument of type '$path'");
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

      } elseif ($path[0] == '/') {
         # Strip first slash from absolute paths
         $path = substr($path, 1);

      } else {
         if (($options['full'] or $options['ssl']) and !preg_match('#^\w+://.#', $path)) {
            # If a full URI for a relative path needs to be generated, use the directory name
            # from the currently requested URI
            $path = substr(dirname($_SERVER['REQUEST_URI']), 1)."/$path";
         } else {
            # Return normal paths and fully-qualified URLs unchanged
            return $path;
         }
      }

      if (array_delete($options, 'full') or $options['ssl']) {
         if (array_delete($options, 'ssl')
            or (Dispatcher::$controller and Dispatcher::$controller->is_ssl())) {
            $url = 'https';
         } else {
            $url = 'http';
         }
         $url .= '://'.$_SERVER['HTTP_HOST'];
      }

      $url .= Dispatcher::$prefix;

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
      $confirm = add_confirm_options($options);

      # Send a POST request by dyamically building a form element
      if (array_delete($options, 'post')) {
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
   function button_to($title, $path, $options=null) {
      add_confirm_options(&$options);
      return form_tag($path, array('method' => any(array_delete($options, 'method'), 'get')))
           . submit_button($title, $options) . "</form>\n";
   }

   # Add necessary options for confirmation, used in link_to() and button_to()
   function add_confirm_options(&$options) {
      if ($confirm = array_delete($options, 'confirm')) {
         $message = ($confirm === true ? _("Are you sure?") : $confirm);
         $options['onclick'] = "return confirm('$message')";
         return $message;
      }
   }

?>
