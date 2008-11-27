<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function is_command($command) {
      $command = preg_replace('/ .*/', '', trim($command));

      if (is_executable($command)) {
         return true;
      } else {
         foreach (explode(':', $_SERVER['PATH']) as $path) {
            if (is_executable("$path/$command")) {
               return true;
            }
         }
      }

      return false;
   }

   function build_shell_command($command, $args=null) {
      if (!is_command($command)) {
         throw new ApplicationError("Command '$command' not found");
      }

      if (count($args) == 1 and is_array($args[0])) {
         $args = $args[0];
      }

      if ($args) {
         $args = array_map('escapeshellarg', $args);
         array_unshift($args, $command);
         $command = call_user_func_array('sprintf', $args);
      }

      return $command;
   }

   # Run a shell command.
   #
   # $command may contain placeholders which will be replaced
   # by the remaining, shell-escaped arguments.
   #
   # Returns true if the command was successful.
   #
   function run($command, $args=null) {
      log_info("Running '$command'");
      $args = array_slice(func_get_args(), 1);
      exec(build_shell_command($command, $args), $output, $status);
      return ($status === 0);
   }

   # Execute a terminal application.
   function term_exec($command, $args=null) {
      log_info("Executing '$command'");
      $args = array_slice(func_get_args(), 1);
      $proc = proc_open(build_shell_command($command, $args), array(), $pipes);

      while (getf(proc_get_status($proc), 'running')) {
         usleep(100);
      }

      return getf(proc_get_Status($proc), 'exitcode');
   }

   # Run a shell command in the background.
   function spawn($command) {
      return run("$command &>/dev/null &", $args);
   }

   # Wrapper for find, returns an array of paths
   function find_files($paths, $options=null, $sort=true) {
      $paths = implode(' ', array_map('escapeshellarg', (array) $paths));
      $sort = ($sort ? '| sort' : '');
      $paths = explode("\n", trim(`find $paths $options 2>/dev/null $sort`));
      return ($paths == array('') ? array() : $paths);
   }

   # Check if path is below a given root
   function check_root($path, $root, $allow_root=true) {
      if ($path[0] != '/' or $root[0] != '/') {
         throw new ApplicationError("Paths must be absolute");
      }

      $path = '/'.trim(($realpath = realpath($path)) ? $realpath : $path, '/');
      $root = '/'.trim($root, '/');

      if (!$allow_root and $path == $root) {
         return false;
      }

      while (true) {
         if ($path == $root) {
            return true;
         } elseif ($path == '/') {
            return false;
         } else {
            $path = dirname($path);
         }
      }
   }

   # Safely delete files
   function rm_f($files) {
      if (!is_array($files)) {
         $files = func_get_args();
      }

      $status = null;
      foreach ($files as $file) {
         $status = @unlink($file);
      }

      return $status;
   }

   # Safely delete directories
   function rm_rf($paths) {
      if (!is_array($paths)) {
         $paths = func_get_args();
      }

      $status = null;
      foreach ($paths as $path) {
         system('rm -rf '.escapeshellarg($path), $status);
      }

      return $status == 0;
   }

   # Create a temporary file which will be automatically deleted when
   # the object instance goes out of scope.
   class Tempfile extends Object
   {
      protected $_path;

      function __construct($name=null) {
         if (function_exists(config)) {
            $name = any($name, config('name'));
         }

         $tmpdir = sys_get_temp_dir();
         $this->_path = trim(`mktemp -p $tmpdir $name.XXXXXX`);
      }

      function __destruct() { rm_f($this->_path); }
      function destroy()    { $this->__destruct(); }
      function exists()     { return is_file($this->_path); }
      function get_path()   { return $this->_path; }

      function read() {
         if ($this->exists()) {
            return file_get_contents($this->_path);
         }
      }

      function write($data, $mode=FILE_APPEND) {
         if ($this->exists()) {
            return file_put_contents($this->_path, $data, $mode);
         }
      }
   }

   # Create a temporary directory which will be automatically deleted when
   # the object instance goes out of scope.
   class Tempdir extends Object
   {
      protected $_path;

      function __construct($name=null) {
         if (function_exists(config)) {
            $name = any($name, config('name'));
         }

         $tmpdir = sys_get_temp_dir();
         $this->_path = trim(`mktemp -d -p $tmpdir $name.XXXXXX`);
      }

      function __destruct() { rm_rf($this->_path); }
      function destroy()    { $this->__destruct(); }
      function exists()     { return is_dir($this->_path); }
      function get_path()   { return $this->_path; }
   }

?>
