<?
/*
   PHP on Crutches - Copyright (c) 2008 Markus Koller

   This program is free software; you can redistribute it and/or modify
   it under the terms of the MIT License.

   $Id$
*/

   # Build the path for an asset.
   function asset_path($file) {
      $path = Dispatcher::$prefix.$file;
      if (file_exists(WEBROOT.$file)) {
         $path .= '?'.filemtime(WEBROOT.$file);
      } else {
         log_error("Asset not found: /$file");
      }

      return $path;
   }

   # Build a stylesheet tag
   function stylesheet_tag($name, $options=null) {
      return tag('link', $options, array(
         'rel' => 'stylesheet', 'type' => 'text/css',
         'href' => asset_path(STYLESHEETS.$name.'.css')
      ));
   }

   # Build a javascript tag
   function javascript_tag($name, $options=null) {
      return content_tag('script', null, $options, array(
         'type' => 'text/javascript',
         'src' => asset_path(JAVASCRIPTS.$name.'.js')
      ));
   }

   # Build an image tag
   function image_tag($name, $options=null) {
      return tag('img', $options, array(
         'src' => asset_path(IMAGES.$name), 'alt' => ''
      ));
   }

?>
