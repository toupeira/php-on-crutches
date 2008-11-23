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

   putenv('LANG=en_US.UTF-8');
   putenv('LANGUAGE=');

   $header = <<<TXT
#, fuzzy
msgid ""
msgstr ""
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"


TXT;

   $templates = array('additional', 'application', 'attributes', 'framework');
   $languages = (array) config('languages');
   $domain = config('name');

   function xgettext($template, $dir=null) {
      $files = find_files($dir, '-name "*.php" -o -name "*.thtml"');
      $files = implode(' ', array_map('escapeshellarg', $files));

      print "Updating [1m$template[0m messages...\n";
      $file = LANG.$template.'.pot';
      file_put_contents($file, $GLOBALS['header']);
      $command = "";

      if (!run("xgettext -j --omit-header --from-code=utf-8 -L php -o '$file' $files")) {
         print "Error while running xgettext\n\n";
         exit(1);
      }

      if (`wc -l $file` > substr_count($GLOBALS['header'], "\n")) {
         # Strip the root directory from paths for portability
         system("sed -ri 's| (".ROOT.")([^ ]+:[0-9]+)| \\2|g' $file");

         # Split multiple paths into separate lines
         system("sed -ri 's|#: ([^ ]+) ([^ ]+)$|#: \\1\\n#: \\2|g' $file");
      } else {
         # Remove the file if it's empty
         unlink($file);
      }
   }

   print "\n";

   xgettext('framework', LIB);
   xgettext('application', APP);

   print "Updating [1mmodel[0m messages...\n";

   $attributes = array();
   $file = fopen(LANG.'attributes.pot', 'w');
   fwrite($file, $header);

   foreach (glob(MODELS.'*.php') as $model) {
      require_once $model;

      $class = camelize(substr(basename($model), 0, -4));
      if (is_subclass_of($class, Model)) {
         $model = new $class();
         $messages = array();

         $key = humanize($class, false);
         if (!in_array($key, $attributes)) {
            $attributes[] = $key;
            $messages[] = "msgid \"$key\"\n"
                        . "msgstr \"\"\n\n";
         }

         foreach ($model->attributes as $key => $value) {
            $key = humanize($key, false);
            if (!in_array($key, $attributes)) {
               $attributes[] = $key;
               $messages[] = "msgid \"$key\"\n"
                           . "msgstr \"\"\n\n";
            }
         }

         if ($messages) {
            fwrite($file, "# $class attributes: \n");
            fwrite($file, implode('', $messages));
         }
      }
   }
   fclose($file);

   print "\nUpdating translations...\n";

   foreach ($languages as $language) {
      print "  [1m$language[0m: ";
      $path = LANG.$language."/LC_MESSAGES/";
      @mkdir($path, 0750, true);

      $all = $path.$domain.'.po';
      rm_f($all);
      touch($all);
      
      foreach ($templates as $template) {
         $messages = "$path$template.po";
         $template = LANG.$template.'.pot';

         if (file_exists($messages)) {
            # Merge existing translations
            run("msgmerge -qU '$messages' '$template'");
         } elseif (is_file($template)) {
            # Create empty translations
            copy($template, $messages);
         } else {
            continue;
         }

         if (!run("msgcat --use-first --to-code=utf-8 -o '$all' '$all' '$messages'")) {
            print "Error while running msgcat\n\n";
            exit(1);
         }
      }

      # Compile translated message catalog
      run("msgfmt -vo '$path$domain.mo' '$all'");
   }

   print "\n";

?>
