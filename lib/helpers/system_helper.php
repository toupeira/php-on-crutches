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

      log_debug("Running '$command'");
      exec($command, $output, $status);
      return ($status === 0);
   }

   # Run a shell command in the background.
   function spawn($command) {
      return run("$command &>/dev/null &", $args);
   }

   # Wrapper for find, returns an array of paths
   function find_files($paths, $options=null) {
      $paths = implode(' ', array_map(escapeshellarg, (array) $paths));
      $command = sprintf('find %s %s 2>/dev/null | sort', $paths, $options);
      $paths = explode("\n", trim(`$command`));
      return ($paths == array('') ? array() : $paths);
   }

   # Create a temporary file or directory which will be removed when
   # the request is finished.
   function mktemp($dir=false) {
      $template = config('application').'.XXXXXX';
      $dir = $dir ? '-d' : '';
      $path = trim(`mktemp $dir -p /tmp $template`);
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
