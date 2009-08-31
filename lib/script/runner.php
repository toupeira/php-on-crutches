#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function usage() {
      print "Usage: {$GLOBALS['argv'][0]} [OPTIONS] CODE\n"
            . "\n"
            . "  -p       Use production environment\n"
            . "  -d       Use development environment\n"
            . "  -t       Use test environment\n"
            . "\n";
      exit(255);
   }

   $args = array_slice($argv, 1);
   $code = array();

   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-p':
            $_ENV['ENVIRONMENT'] = 'production';
            break;
         case '-d':
            $_ENV['ENVIRONMENT'] = 'development';
            break;
         case '-t':
            $_ENV['ENVIRONMENT'] = 'test';
            break;
         default:
            if ($arg[0] == '-') {
               usage();
            } else {
               $code[] = $arg;
            }
      }
   }

   if ($code) {
      require_once dirname(__FILE__).'/../script.php';
      eval(implode("; ", $code).";");
   } else {
      usage();
   }

?>
