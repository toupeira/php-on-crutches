<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build a stylesheet tag
   function stylesheet_tag($name, array $options=null) {
      return tag('link', $options, array(
         'rel' => 'stylesheet', 'type' => 'text/css',
         'href' => asset_path(STYLESHEETS, $name, '.css')
      )).N;
   }

   # Build a javascript tag
   function javascript_tag($name, array $options=null) {
      return content_tag('script', null, $options, array(
         'type' => 'text/javascript',
         'src' => asset_path(JAVASCRIPTS, $name, '.js')
      )).N;
   }

   # Add stylesheets from a template
   function add_stylesheets($name) {
      return $GLOBALS['_ASSETS']['.css'] = array_merge(
         (array) $GLOBALS['_ASSETS']['.css'], func_get_args()
      );
   }

   # Add javascripts from a template
   function add_javascripts($name) {
      return $GLOBALS['_ASSETS']['.js'] = array_merge(
         (array) $GLOBALS['_ASSETS']['.js'], func_get_args()
      );
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
      if (is_array($assets[count($assets) - 1])) {
         $options = array_pop($assets);
      } else {
         $options = null;
      }

      if (config('debug_toolbar')) {
         if ($ext == '.js') {
            $assets[] = 'framework/prototype';
         }
         $assets[] = 'framework/toolbar';
      }

      if ($add = $GLOBALS['_ASSETS'][$ext]) {
         $assets = array_merge($assets, $add);
      }

      if ($assets) {
         $assets = array_unique($assets);
      } else {
         return;
      }

      if ($name = $options['name']) {
         unset($options['name']);
      } else {
         $name = 'all';
      }

      # Combine multiple assets
      if (config('merge_assets') and count($assets) > 1) {
         $all = WEBROOT.$dir.$name.$ext;

         # Create the combined file if necessary
         if (!is_file($all)) {
            # Build the file paths
            $paths = array();
            foreach ($assets as $asset) {
               if (is_file($path = WEBROOT.$dir.$asset.$ext)) {
                  $paths[$asset] = $path;
               }
            }

            # Combine the contents
            $output = '';
            foreach ($paths as $asset => $path) {
               $depth = substr_count($asset, '/');
               $source = fopen($path, 'r');
               while ($input = fgets($source)) {
                  # Tweak URL references in CSS files
                  if ($ext == '.css' and $depth) {
                     # Add folder name for relative paths
                     $input = preg_replace('#url\(([^\./].*)\)#', 'url('.substr($asset, 0, strrpos($asset, '/')).'/\1)', $input);
                     # Strip '../' in paths
                     $input = preg_replace('#url\(((\.\./){'.$depth.'})#', 'url(', $input);
                  }
                  $output .= $input;
               }
            }

            if ($ext == '.css') {
               # Minify CSS
               require_once LIB.'vendor/cssmin.php';
               $output = cssmin::minify($output);
            } elseif ($ext == '.js') {
               # Minify JavaScript
               require_once LIB.'vendor/jsmin.php';
               $output = JSMin::minify($output);
            }

            if (@file_put_contents($all, $output)) {
               log_info("Created merged asset: $all");
            } else {
               log_error("Couldn't create merged asset: $all");
            }
         }

         if (is_file($all)) {
            return $tag($name, $options);
         }

         # If the file couldn't be created,
         # fall through to the default behaviour
      }
      
      $html = '';
      foreach ($assets as $asset) {
         $html .= $tag($asset, $options);
      }

      return $html;
   }

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

         if (is_file(WEBROOT.$path)) {
            $web_path .= '?'.filemtime(WEBROOT.$path);
         } else {
            log_error("Asset not found: /$path");
         }

         if ($host = config('asset_host')) {
            $web_path = url_for($web_path, array('host' => $host));
         }

         return $web_path;
      }
   }

   # Build an image tag
   function image_tag($file, array $options=null) {
      return tag('img', $options, array(
         'src' => asset_path(IMAGES, $file), 'alt' => ''
      ));
   }

   define_default('ICON_WIDTH', 16);
   define_default('ICON_HEIGHT', 16);
   define_default('ICON_PREFIX', 'icons/');
   define_default('ICON_SUFFIX', '.png');

   # Build an image tag for a 16x16 PNG icon
   function icon($name, $options=null) {
      if (strpos($name, '/') === false) {
         $name = ICON_PREFIX.$name;
      }

      if ($name[0] != '/') {
         $name .= ICON_SUFFIX;
      }

      return image_tag($name, array_merge(
         array(
            'width' => ICON_WIDTH,
            'height' => ICON_HEIGHT,
            'class' => 'icon'
         ), (array) $options
      )).' ';
   }

   function icon_link_to($icon, $title, $path, array $options=null, array $url_options=null) {
      if (is_null($options['title']) and !$icon_title = array_delete($options, 'icon_title')) {
         $icon_title = strip_html($title);
      }

      $title = icon($icon, array('title' => $icon_title)).$title;
      return link_to($title, $path, $options, $url_options);
   }

   function icon_link_to_function($icon, $title, $code, array $options=null) {
      $options['onclick'] = "$code; return false";
      return icon_link_to($icon, $title, '#', $options);
   }

?>
