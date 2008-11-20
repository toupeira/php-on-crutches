#!/usr/bin/php5
<? # vim: ft=php
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../script.php';

   if (!run('lighttpd -v')) {
      print "You need lighttpd to run the server.\n";
      exit(1);
   }

   $port = 3000;
   $ip = "0.0.0.0";
   $php = "/usr/bin/php-cgi";
   $verbose = true;

   function usage() {
      print "Usage: {$GLOBALS['argv'][0]} [OPTIONS]\n"
          . "\n"
          . "  -p PORT       Run the server on the specified port (Default: {$GLOBALS['port']})\n"
          . "  -b BINDING    Bind the server to the specified IP (Default: {$GLOBALS['ip']})\n"
          . "  -e PATH       Path to the PHP executable (Default: {$GLOBALS['php']})\n"
          . "  -q            Print as little as possible\n"
          . "\n";
      exit(255);
   }

   $args = array_slice($argv, 1);
   while ($arg = array_shift($args)) {
      switch ($arg) {
         case '-p':
            if (!is_numeric($port = array_shift($args))) {
               usage();
            }
            break;
         case '-b':
            if (!preg_match('/^[\d\.]{7,15}$/', $ip = array_shift($args))) {
               usage();
            }
            break;
         case '-e':
            if (!ctype_print($php = array_shift($args))) {
               usage();
            }
            break;
         case '-q':
            $verbose = false;
            break;
         default:
            usage();
      }
   }

   $config = new Tempfile();
   $socket = new Tempfile();
   $webroot = WEBROOT;

   $config->write(<<<CONF

server.port = $port
server.bind = "$ip"
server.document-root = "$webroot"
server.modules = ( "mod_accesslog", "mod_rewrite", "mod_fastcgi" )

include_shell "/usr/share/lighttpd/create-mime.assign.pl"

index-file.names = ( "index.php" )
static-file.exclude-extensions = ( ".php", ".fcgi" )

fastcgi.server = ( ".php" => ( "localhost" => (
   "socket" => "{$socket->path}",
   "bin-path" => "$php"
)))

url.rewrite-once = (
   "^/(index\.(php|fcgi)|images|stylesheets|javascripts).*$" => "$0",
   "^(.*)$" => "/index.php?path=$1",
)

CONF
   );

   if ($verbose) {
      print "\n\nStarting lighttpd on $ip:$port...\n\n";
      $config->write(<<<CONF
accesslog.filename = "/proc/" + var.PID + "/fd/2"
accesslog.format = "%h %V %t \"%r\" %>s %b \"%{Referer}i\""
CONF
      );
   }

   # Ignore interrupt signal
   pcntl_signal(2, proc(''));

   system("lighttpd -D -f {$config->path}");

   if ($verbose) {
      print "\n\n\n";
   }

?>
