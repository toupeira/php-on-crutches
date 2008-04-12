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
   $master = LANG.$domain.'.pot';
   $additional = LANG.'additional.pot';

   $source_paths = array(APP, CONFIG, LIB);
   $source_files = find_files($source_paths, '-name "*.php" -o -name "*.thtml"');
   $source_files = implode(' ', array_map(escapeshellarg, $source_files));

   print "\n";

   print "Updating strings...\n";

   if (!run("xgettext --omit-header -L php -o '$master' $source_files")) {
      print "Error while running xgettext\n\n";
      exit(1);
   }

   if (is_file($additional) and !run("msgcat -o '$master' '$master' '$additional'")) {
      print "Error while running msgcat\n\n";
      exit(1);
   }

   print "Updating language templates...\n";

   foreach ($languages as $language) {
      $path = LANG.$language."/LC_MESSAGES/$domain";
      $template = "$path.po";
      $compiled = "$path.mo";

      print "  $language: ";

      if (file_exists($template)) {
         run("msgmerge -qU '$template' '$master'");
      } else {
         mkdir(dirname($template), 0750, true);
         copy($master, $template);
      }

      run("msgfmt -vo '$compiled' '$template'");
   }

   print "\n";

?>
