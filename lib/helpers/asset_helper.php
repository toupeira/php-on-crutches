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

         if (file_exists(WEBROOT.$path)) {
            $web_path .= '?'.filemtime(WEBROOT.$path);
         } else {
            log_error("Asset not found: /$path");
         }

         return $web_path;
      }
   }

   # Include multiple stylesheets
   function include_stylesheets($args) {
      $args = func_get_args();
      return merge_assets(stylesheet_tag, STYLESHEETS, '.css', $args);
   }

   # Include multiple javascripts
   function include_javascripts($args) {
      $args = func_get_args();
      return merge_assets(javascript_tag, JAVASCRIPTS, '.js', $args);
   }

   # Merge multiple assets into one file and return a tag
   function merge_assets($tag, $dir, $ext, $assets) {
      if (config('debug')) {
         $assets[] = 'framework/debug';
      }
      $assets = array_unique($assets);

      if (config('merge_assets') and count($assets) > 1) {
         $mtime = 0;
         $all = WEBROOT.$dir.'all'.$ext;
         $paths = array();
         foreach ($assets as $asset) {
            if (is_file($path = WEBROOT.$dir.$asset.$ext)) {
               $paths[$asset] = $path;
               $mtime = max($mtime, filemtime($path));
            }
         }

         while (true) {
            if (!is_file($all) or $mtime > filemtime($all)) {
               if (!$target = @fopen($all, 'w')) {
                  log_error("Couldn't create merged asset: $all");

                  # Fallthrough to unmerged assets
                  break;
               }

               foreach ($paths as $asset => $path) {
                  $source = fopen($path, 'r');
                  while ($input = fread($source, 8192)) {
                     fwrite($target, $input, 8192);
                  }
                  fclose($source);
               }
               fclose($target);
               log_info("Created merged asset: $all");
            }

            return $tag('all');
         }
      }
      
      $html = '';
      foreach ($assets as $asset) {
         $html .= $tag($asset)."\n";
      }

      return $html;
   }

   # Build a stylesheet tag
   function stylesheet_tag($file, array $options=null) {
      return tag('link', $options, array(
         'rel' => 'stylesheet', 'type' => 'text/css',
         'href' => asset_path(STYLESHEETS, $file, '.css')
      ));
   }

   # Build a javascript tag
   function javascript_tag($file, array $options=null) {
      return content_tag('script', null, $options, array(
         'type' => 'text/javascript',
         'src' => asset_path(JAVASCRIPTS, $file, '.js')
      ));
   }

   # Build an image tag
   function image_tag($file, array $options=null) {
      return tag('img', $options, array(
         'src' => asset_path(IMAGES, $file), 'alt' => ''
      ));
   }

?>
