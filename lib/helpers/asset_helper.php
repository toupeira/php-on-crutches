<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build the path for an asset.
   function asset_path($directory, $file, $ext='') {
      if ($file[0] == '/' or preg_match('#^\w+://.#', $file)) {
         # Leave absolute paths and fully-qualified URLs alone
         return $file;
      } else {
         $path = $directory.$file.$ext;
         $web_path = Dispatcher::$prefix.$path;
         #$path = Dispatcher::$prefix.$directory.$file.$ext;

         if (file_exists(WEBROOT.$path)) {
            $web_path .= '?'.filemtime(WEBROOT.$path);
         } else {
            log_error("Asset not found: /$path");
         }

         return $web_path;
      }
   }

   # Build a stylesheet tag
   function stylesheet_tag($file, $options=null) {
      return tag('link', $options, array(
         'rel' => 'stylesheet', 'type' => 'text/css',
         'href' => asset_path(STYLESHEETS, $file, '.css')
      ));
   }

   # Build a javascript tag
   function javascript_tag($file, $options=null) {
      return content_tag('script', null, $options, array(
         'type' => 'text/javascript',
         'src' => asset_path(JAVASCRIPTS, $file, '.js')
      ));
   }

   # Build an image tag
   function image_tag($file, $options=null) {
      return tag('img', $options, array(
         'src' => asset_path(IMAGES, $file), 'alt' => ''
      ));
   }

?>
