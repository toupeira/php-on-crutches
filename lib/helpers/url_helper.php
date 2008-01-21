<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build a URL for a given path
   function url_for($path) {
      if (preg_match('#^(/|\w+://)#', $path)) {
         # Return absolute paths and fully-qualified URIs unchanged
         return $path;
      } else {
         # Replace default parts ('.') with the current
         # controller or action name.
         $parts = explode('/', $path);
            if ($parts[0] == '.') $parts[0] = Dispatcher::$controller->name;
            if ($parts[1] == '.') $parts[1] = Dispatcher::$action;
         $path = implode('/', $parts);

         # Append the path to the query string if URL
         # rewriting is disabled.
         if (!config('rewrite_urls')) {
            $path = "index.php?path=$path";
         }

         return Dispatcher::$prefix.$path;
      }
   }

   # Build a link tag
   function link_to($title, $path, $options=null) {
      return content_tag('a', $title, $options, array('href' => url_for($path)));
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
