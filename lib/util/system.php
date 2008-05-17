<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Run a shell command.
   #
   # $command may contain placeholders which will be replaced
   # by the remaining, shell-escaped arguments.
   #
   # Returns true if the command was successful.
   #
   function run($command, $args=null) {
      if (!is_array($args)) {
         $args = array_slice(func_get_args(), 1);
      }

      if ($args) {
         $args = array_map(escapeshellarg, $args);
         array_unshift($args, $command);
         $command = call_user_func_array(sprintf, $args);
      }

      log_info("Running '$command'");
      exec($command, $output, $status);
      return ($status === 0);
   }

   # Run a shell command in the background.
   function spawn($command) {
      return run("$command &>/dev/null &", $args);
   }

   # Wrapper for find, returns an array of paths
   function find_files($paths, $options=null, $sort=true) {
      $paths = implode(' ', array_map(escapeshellarg, (array) $paths));
      $sort = ($sort ? '| sort' : '');
      $paths = explode("\n", trim(`find $paths $options 2>/dev/null $sort`));
      return ($paths == array('') ? array() : $paths);
   }

   # Create a temporary file or directory which will be removed when
   # the request is finished.
   function mktemp($dir=false) {
      $tmpdir = sys_get_temp_dir();
      $template = config('name').'.XXXXXX';
      $dir = $dir ? '-d' : '';
      $path = trim(`mktemp $dir -p $tmpdir $template`);
      register_shutdown_function(rm_rf, $path);
      return $path;
   }

   function rm_f($file) {
      if (file_exists($file)) {
         return @unlink($file);
      }
   }

   function rm_rf($file) {
      if (file_exists($file)) {
         return system("rm -rf ".escapeshellarg($file));
      }
   }

?>
