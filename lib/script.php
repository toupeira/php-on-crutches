<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require dirname(__FILE__).'/../config/environment.php';

   # Set fake request information, useful for testing and the console
   function fake_request($path=null, $method='GET', $ssl=false) {
      $_SERVER['HTTP_HOST'] = 'www.example.com';
      $_SERVER['REQUEST_URI'] = "/$path";
      $_SERVER['REMOTE_ADDR'] = any($_SERVER['REMOTE_ADDR'], '127.0.0.1');
      $_SERVER['REQUEST_METHOD'] = $method;
      $_SERVER['HTTPS'] = $ssl ? 'on' : null;
   }

   log_level_set(LOG_DISABLED);
   fake_request();

   # Disable all time limits
   ini_set('max_execution_time', 0);
   ini_set('max_input_time', 0);
   set_time_limit(0);

?>
