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

   $framework = LANG.'framework.pot';
   $application = LANG.'application.pot';
   $additional = LANG.'additional.pot';

   $framework_files = find_files(LIB, '-name "*.php" -o -name "*.thtml"');
   $framework_files = implode(' ', array_map(escapeshellarg, $framework_files));

   $application_files = find_files(APP, '-name "*.php" -o -name "*.thtml"');
   $application_files = implode(' ', array_map(escapeshellarg, $application_files));

   print "\n";

   print "Updating framework messages...\n";
   if (!run("xgettext --from-code=utf-8 -L php -o '$framework' $framework_files")) {
      print "Error while running xgettext\n\n";
      exit(1);
   }

   print "Updating application messages...\n";
   if (!run("xgettext --from-code=utf-8 -L php -o '$application' $application_files")) {
      print "Error while running xgettext\n\n";
      exit(1);
   }

   print "Updating language templates...\n";

   foreach ($languages as $language) {
      print "  $language: ";
      $path = LANG.$language."/LC_MESSAGES/";
      $all = $path.$domain.'.po';
      rm_f($all);
      touch($all);
      
      foreach (array('framework', 'application', 'additional') as $master) {
         $messages = "$path$master.po";

         if (file_exists($messages)) {
            # Merge existing translations
            run("msgmerge -qU '$messages' '{$$master}'");
         } elseif (is_file($$master)) {
            # Create empty translations
            @mkdir(dirname($messages), 0750, true);
            copy($$master, $messages);
         } else {
            continue;
         }

         if (!run("msgcat --use-first --to-code=utf-8 -o '$all' '$all' '$messages'")) {
            print "Error while running msgcat\n\n";
            exit(1);
         }
      }

      # Compile message catalog
      run("msgfmt -vo '$path$domain.mo' '$all'");
   }

   print "\n";

?>
