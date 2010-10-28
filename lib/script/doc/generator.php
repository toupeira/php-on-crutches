<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require LIB.'script/doc/parser.php';
   require LIB.'script/doc/view_helper.php';

   class DocGenerator extends Object
   {
      protected $_paths;
      protected $_excludes;
      protected $_target;
      protected $_title;
      protected $_verbose;

      protected $_view_path;

      protected $_data;
      protected $_files;
      protected $_classes;
      protected $_functions;

      function __construct($paths, $excludes=null, $title=null) {
         $this->_paths = (array) $paths;
         $this->_excludes = (array) $excludes;
         $this->_title = any($title, humanize(config('name')).' Documentation');
         $this->_view_path = LIB.'script/doc/views/';
      }

      function get_data() {
         return (array) $this->_data;
      }

      function get_files() {
         return (array) $this->_files;
      }

      function get_classes() {
         return (array) $this->_classes;
      }

      function get_functions() {
         return (array) $this->_functions;
      }

      function generate($target, $force=false, $verbose=false) {
         $this->_target = $target;
         $this->_verbose = $verbose;

         print "\nGenerating documentation in [1m{$this->_target}[0m...\n";

         if (file_exists($this->_target) and !$force) {
            print "Error: Target path {$this->_target} already exists.\n";
            return false;
         } else { 
            rm_rf($this->_target);
         }

         if (!mkdir($this->_target)) {
            print "Error: Could not create target path {$this->_target}.\n";
            return false;
         }

         if (!copy($this->_view_path.'doc.css', "{$this->_target}/doc.css") or
                   !copy($this->_view_path.'doc.js', "{$this->_target}/doc.js")) {
            print "Error: Could not copy assets.\n";
            return false;
         }

         $this->_data = $this->_files = $this->_classes = $this->_functions = null;
         foreach ($this->_paths as $path) {
            $this->generate_path($path);
         }

         if (!$this->_verbose) {
            print "\n";
         }

         print "  rendering files: ";
         foreach ($this->_data as $file => $data) {
            $classes = array();
            foreach ($data['classes'] as $class => $class_data) {
               $template = $this->_classes[$class];
               $classes[$class] = $template;

               $this->render('class', $this->_classes[$class], array(
                  'title'      => $class,
                  'class_name' => $class,
                  'class'      => $class_data,
               ));
            }

            if ($template = $this->_files[$file]) {
               $this->render('file', $template, array(
                  'title'     => basename($file),
                  'file'      => $file,
                  'data'      => $data,
                  'classes'   => $classes,
                  'constants' => $data['constants'],
                  'functions' => $data['functions'],
                  'comment'   => $data['comment'],
               ));
            }
         }
         print "\n";

         print "  rendering index: ";
         $this->render('index');
         print "\n\n";

         return true;
      }

      protected function generate_path($path) {
         static $_current;
         static $_show_dir = false;

         $path = rtrim($path, '/');
         if (in_array(realpath($path), $this->_excludes)) {
            return;
         } elseif (is_dir($path)) {
            $_current = str_replace(ROOT, '', $path);
            $_show_dir = true;

            $files = array();
            $dirs = array();
            foreach (glob("$path/*") as $path) {
               if (is_file($path)) {
                  $files[] = $path;
               } elseif (is_dir($path)) {
                  $dirs[] = $path;
               }
            }

            foreach (sorted($files) as $file) { $this->generate_path($file); }
            foreach (sorted($dirs) as $dir)   { $this->generate_path($dir); }

         } elseif (is_file($path) and strtolower(substr($path, -4)) == '.php') {
            if ($_show_dir and $_current) {
               print "\n  $_current:\n";
               $_show_dir = false;
            }

            printf("    %s: ", basename($path));

            if ($this->_verbose) {
               print "\n";
            }

            $parser = new DocParser($path);
            $parser->parse($this->_verbose);
            $this->merge($parser);

            print "\n";
         }
      }

      protected function merge($parser) {
         if ($parser->classes or $parser->functions or $parser->constants) {
            $path = str_replace(ROOT, '', $parser->path);
            $template = 'file_'.str_replace('/', '_', strtolower(trim($path, '/')));

            $this->_files[$path] = $template;

            foreach ($parser->functions as $function => $data) {
               $this->_functions[$function] = $template;
            }

            $classes = array();
            foreach ($parser->classes as $class => $data) {
               $data['file'] = $path;
               $classes[$class] = $data;

               $template = 'class_'.underscore($class);
               $this->_classes[$class] = $template;
            }

            $this->_data[$path] = array(
               'classes'   => $classes,
               'functions' => $parser->functions,
               'constants' => $parser->constants,
               'comment'   => $parser->comment,
            );
         }
      }

      protected function render($template, $target=null, array $data=null) {
         $target = any($target, $template);

         $view = new View();
         $view->add(array(
            'main_title'  => $this->_title,
            'all_files'   => $this->files,
            'all_classes' => $this->classes,
            'all_functions'   => $this->functions,
         ));
         $view->add($data);

         file_put_contents(
            $this->_target."/$target.html",
            $view->render(
               $this->_view_path.$template.'.thtml',
               $this->_view_path.'layout.thtml'
            )
         );
         print ".";
      }
   }

?>
