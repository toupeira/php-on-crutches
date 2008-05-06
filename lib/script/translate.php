#!/usr/bin/php5
<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   require_once dirname(__FILE__).'/../script.php';

   $languages = (array) config('languages');
   $domain = config('name');

   $master = LANG.$domain.'.pot';
   $additional = LANG.'additional.pot';

   $source_paths = array(APP, CONFIG, LIB);
   $source_files = find_files($source_paths, '-name "*.php" -o -name "*.thtml"');
   $source_files = implode(' ', array_map(escapeshellarg, $source_files));

   print "\n";

   print "Updating messages...\n";

   # Extract messages from code files
   if (!run("xgettext --from-code=utf-8 -L php -o '$master' $source_files")) {
      print "Error while running xgettext\n\n";
      exit(1);
   }

   # Merge additional messages
   if (is_file($additional) and !run("msgcat --to-code=utf-8 -o '$master' '$master' '$additional'")) {
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
         # Merge existing translations
         run("msgmerge -qU '$template' '$master'");
      } else {
         # Create empty template
         @mkdir(dirname($template), 0750, true);
         copy($master, $template);
      }

      # Compile message catalog
      run("msgfmt -vo '$compiled' '$template'");
   }

   print "\n";

?>
