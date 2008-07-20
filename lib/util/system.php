<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function build_shell_command($command, $args=null) {
      if (count($args) == 1 and is_array($args[0])) {
         $args = $args[0];
      }

      if ($args) {
         $args = array_map(escapeshellarg, $args);
         array_unshift($args, $command);
         $command = call_user_func_array(sprintf, $args);
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
      $args = func_get_args();
      return proc_open(build_shell_command($command, $args), array(), $pipes);
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
