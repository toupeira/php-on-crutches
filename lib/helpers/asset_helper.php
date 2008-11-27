<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build the path for an asset, append the last modification time for local files
   function asset_path($directory, $file, $ext='') {
      if ($file[0] == '/' or preg_match('#^\w+://.#', $file)) {
         # Leave absolute paths and fully-qualified URLs alone
         return $file;
      } elseif (substr($file, 0, 2) == './') {
         # Convert relative paths
         return substr($file, 2).$ext;
      } else {
         $path = $directory.$file.$ext;
         $web_path = config('prefix').$path;

         if (file_exists(WEBROOT.$path)) {
            $web_path .= '?'.filemtime(WEBROOT.$path);
         } else {
            log_error("Asset not found: /$path");
         }

         return $web_path;
      }
   }

   # Include multiple stylesheets
   function include_stylesheets() {
      $args = func_get_args();
      return merge_assets(stylesheet_tag, STYLESHEETS, '.css', $args);
   }

   # Include multiple javascripts
   function include_javascripts() {
      $args = func_get_args();
      return merge_assets(javascript_tag, JAVASCRIPTS, '.js', $args);
   }

   # Merge multiple assets into one file and return a tag
   function merge_assets($tag, $dir, $ext, $assets) {
      if (config('debug_toolbar')) {
         if ($ext == '.js') {
            $assets[] = 'framework/prototype';
         }
         $assets[] = 'framework/toolbar';
      }

      if ($assets) {
         $assets = array_unique($assets);
      } else {
         return;
      }

      # Combine multiple assets
      if (config('merge_assets') and count($assets) > 1) {
         $all = WEBROOT.$dir.'all'.$ext;

         # Build the file paths and get the last modification time
         $paths = array();
         $mtime = 0;
         foreach ($assets as $asset) {
            if (is_file($path = WEBROOT.$dir.$asset.$ext)) {
               $paths[$asset] = $path;
               $mtime = max($mtime, filemtime($path));
            }
         }

         # Create the combined file when necessary
         if (!is_file($all) or $mtime > filemtime($all)) {
            if ($target = @fopen($all, 'w')) {
               foreach ($paths as $asset => $path) {
                  $depth = substr_count($asset, '/');
                  $source = fopen($path, 'r');
                  while ($input = fgets($source)) {
                     if ($ext == '.css' and $depth) {
                        $input = preg_replace('#url\(((\.\./){'.$depth.'})#', 'url(', $input);
                     }
                     fputs($target, $input);
                  }
                  fclose($source);
               }
               fclose($target);
               log_info("Created merged asset: $all");
            } else {
               log_error("Couldn't create merged asset: $all");
            }
         }

         if (is_file($all)) {
            return $tag('all').N;
         }

         # If the file couldn't be created,
         # fall through to the default behaviour
      }
      
      $html = '';
      foreach ($assets as $asset) {
         $html .= $tag($asset);
      }

      return $html;
   }

   # Build a stylesheet tag
   function stylesheet_tag($file, array $options=null) {
      return tag('link', $options, array(
         'rel' => 'stylesheet', 'type' => 'text/css',
         'href' => asset_path(STYLESHEETS, $file, '.css')
      )).N;
   }

   # Build a javascript tag
   function javascript_tag($file, array $options=null) {
      return content_tag('script', null, $options, array(
         'type' => 'text/javascript',
         'src' => asset_path(JAVASCRIPTS, $file, '.js')
      )).N;
   }

   # Build an image tag
   function image_tag($file, array $options=null) {
      return tag('img', $options, array(
         'src' => asset_path(IMAGES, $file), 'alt' => ''
      ));
   }

   # Build an image tag for a 16x16 PNG icon
   function icon($name, $options=null) {
      return image_tag("$name.png", array_merge(
         array('width' => 16, 'height' => 16, 'class' => 'icon'), (array) $options
      ));
   }

?>
