<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function doc_link($title, $template, $anchor=null) {
      return link_to($title, "./$template.html$anchor", array('target' => 'content'));
   }

   function class_link($class, $label=null) {
      $link = any($label, $class);
      $classes = View::$current->get('all_classes');
      if ($template = $classes[$class]) {
         $link = icon('./class').doc_link($link, $template);
      }

      return $link;
   }

   function file_link($file, $label=null) {
      $link = any($label, $class);
      $files = View::$current->get('all_files');
      if ($template = $files[$file]) {
         $link = doc_link($link, $template);
      }

      return $link;
   }

   function auto_link_comment($match) {
      $name = $match[1];
      if (ctype_upper($name[0])) {
         return class_link($name);
      } else {
         return file_link($name);
      }
   }

   function render_comment($comment) {
      if ($comment) {
         return content_tag(
            'div',
            br2nl(textilize(preg_replace_callback(
               '/@([A-Z][\w]*|[a-z][-_\w\/\.]*\.php)@/',
               auto_link_comment, $comment
            ))),
            array('class' => 'comment')
         );
      }
   }

   function render_items($view, $class, $type) {
      $output = '';
      foreach (array('class', 'instance') as $scope) {
         foreach (array('public', 'protected', 'private') as $visibility) {
            $key = $visibility.'_'.$scope.'_'.$type;
            if ($items = $class[$key]) {
               if ($type == 'methods') {
                  $items = $view->render_partial('functions', array(
                     'functions' => $items,
                  ));
               } else {
                  $items = $view->render_partial('properties', array(
                     'properties' => $items,
                     'class'      => $class,
                  ));
               }

               if ($items) {
                  $output .= content_tag('h2', titleize($key, false),
                                array('class' => str_replace('_', ' ', $key))).N;

                  if ($type == 'properties') {
                     $items = "<ul class=\"properties\">\n$items</ul>\n";
                  }

                  $output .= $items;
               }
            }
         }
      }

      if ($output) {
         return $output.N;
      }
   }

   function render_tree($files) {
      $tree = array();
      foreach ($files as $file => $template) {
         $parts = explode('/', trim($file, '/'));
         $last = count($parts) - 1;

         $parent = &$tree;
         foreach ($parts as $i => $part) {
            if ($i == $last) {
               $parent[] = icon('./file').file_link($file, basename($file));
            } else {
               $label = icon('./folder').link_to_function($part, 'toggle_folder(this)');

               if (!$parent[$label]) {
                  $parent[$label] = array();
               }
               $parent = &$parent[$label];
            }
         }
      }

      return list_tag($tree, array('collapse' => 'true'));
   }

?>
