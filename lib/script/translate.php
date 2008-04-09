#!/usr/bin/php5
<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../../config/environment.php';

   $_LOGGER->level = LOG_DISABLED;

   $languages = config('languages');
   $domain = config('application');
   $master = LANG.$domain.'.po';

   $source_paths = array(APP, CONFIG, LIB);
   $source_files = find_files($source_paths, '-name "*.php" -o -name "*.thtml"');
   $source_files = implode(' ', array_map(escapeshellarg, $source_files));

   print "\n";

   print "Updating strings...\n";
   if (!run("xgettext -L php --omit-header -o '$master' $source_files")) {
      print "Error while running xgettext\n\n";
      exit(1);
   }

   print "Updating language templates...\n";
   foreach ($languages as $language) {
      $path = LANG.$language."/LC_MESSAGES/$domain";
      $template = "$path.po";
      $compiled = "$path.mo";

      print "  $language\n";
      if (file_exists($template)) {
         run("msgmerge -U '$template' '$master'");
      } else {
         mkdir(dirname($template), 0750, true);
         copy($master, $template);
      }

      run("msgfmt -o '$compiled' '$template'");
   }

   print "\n";

?>
