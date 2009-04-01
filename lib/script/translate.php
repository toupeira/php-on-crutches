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

   $languages = (array) config('languages');
   $domain = config('name');

   $application = LANG.'application.pot';
   $javascript = LANG.'javascript.pot';

   function xgettext($template, $dir=null, $type='php') {
      $extensions = array('php', 'thtml', 'js');
      $exclude = WEBROOT.JAVASCRIPTS.'all*';

      $filter = "\( -iname '*.".implode("' -o -iname '*.", $extensions)."' \) -not -iname '$exclude'";

      $files = find_files($dir, $filter);
      $files = implode(' ', array_map('escapeshellarg', $files));

      print "Updating [1m$template[0m messages...\n";
      $file = LANG.$template.'.pot';
      file_put_contents($file, $GLOBALS['header']);
      $command = "";

      if ($type != 'php') {
         $silent = '2>/dev/null';
      } else {
         $silent = '';
      }

      if (!run("xgettext -j --omit-header --from-code=utf-8 -L $type -o '$file' $files $silent")) {
         print "Error while running xgettext\n\n";
         exit(1);
      }

      if (`wc -l $file` > substr_count($GLOBALS['header'], "\n")) {
         # Strip the root directory from paths for portability
         system("sed -ri 's| (".ROOT.")([^ ]+:[0-9]+)| \\2|g' $file");

         # Split multiple paths into separate lines
         system("sed -ri 's|#: ([^ ]+) ([^ ]+)$|#: \\1\\n#: \\2|g' $file");
      }
   }

   function merge($target, $source) {
      if (!is_file($source)) {
         return false;
      } elseif (run("msgcat --use-first --to-code=utf-8 -o '%s' '%s' '%s'", $target, $target, $source)) {
         return true;
      } else {
         print "Error while running msgcat\n\n";
         exit(1);
      }
   }

   print "\n";

   xgettext('framework', LIB);
   xgettext('application', APP);

   xgettext('javascript', WEBROOT.JAVASCRIPTS, 'python');
   merge($application, $javascript);

   merge($application, LANG.'additional.pot');

   print "Updating [1mmodel[0m messages...\n";

   $models = array();
   $models_messages = array();
   $attributes = array();
   $attributes_messages = array();

   foreach (glob(MODELS.'*.php') as $model) {
      require_once $model;

      $class = camelize(substr(basename($model), 0, -4));
      if (class_exists($class) and is_subclass_of($class, Model) and $ref = new ReflectionClass($class) and $ref->isInstantiable()) {
         $model = new $class();

         $model_singular = humanize($class, false);
         $model_plural   = humanize(pluralize($class), false);
         if (!in_array($class, $models)) {
            $models[] = $class;
            $models_messages[] = "# Model $class:\n"
                               . "msgid \"$model_singular\"\n"
                               . "msgid_plural \"$model_plural\"\n"
                               . "msgstr[0] \"\"\n"
                               . "msgstr[1] \"\"\n\n";
         }

         if ($all_attributes = array_merge(
            array_keys($model->attributes),
            $model->virtual_attributes
         )) {
            $attributes_messages[] = "# $class attributes:\n";
            foreach ($all_attributes as $key) {
               $key = humanize($key, false);
               if (!in_array($key, $attributes)) {
                  $attributes[] = $key;
                  $attributes_messages[] = "msgid \"$key\"\n"
                                       . "msgstr \"\"\n\n";
               }
            }
         }
      }
   }

   if ($models_messages) {
      $file = fopen(LANG.'models.pot', 'w');
      fwrite($file, $header);
      fwrite($file, implode('', $models_messages));
      fclose($file);
      merge($application, LANG.'models.pot');
   }

   if ($attributes_messages) {
      $file = fopen(LANG.'attributes.pot', 'w');
      fwrite($file, $header);
      fwrite($file, implode('', $attributes_messages));
      fclose($file);
      merge($application, LANG.'attributes.pot');
   }

   print "\nUpdating translations...\n";

   foreach ($languages as $language) {
      print "  [1m$language[0m: ";
      $path = LANG.$language."/LC_MESSAGES/";
      @mkdir($path, 0750, true);

      $all = "$path$domain.po";
      rm_f($all);
      touch($all);

      foreach (array('application', 'framework') as $template) {
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

         merge($all, $messages);
      }

      # Compile translated message catalog
      if (run("msgfmt -vo '$path$domain.mo' '$all'")) {
         #touch("$path$domain.mo");
      } else {
         print "Error while running msgfmt\n\n";
         exit(1);
      }
   }

   if (is_file($javascript)) {
      print "\nUpdating JavaScript translations...\n";

      $keys = array();
      $plurals = array();

      $file = fopen($javascript, 'r');
      while (!feof($file)) {
         if (preg_match('/^msgid(_plural)? "(.*)"$/', fgets($file), $match)) {
            $key = $match[2];

            if (empty($key)) {
               # Look for a multi-line msgid
               while (preg_match('/^"(.+)"$/', fgets($file), $match)) {
                  $key .= $match[2];
               }
            }

            if ($key) {
               if ($match[1]) {
                  $plurals[] = $key;
               } else {
                  $keys[] = $key;
               }
            }
         }
      }
      fclose($file);

      foreach ($languages as $language) {
         print "  [1m$language[0m: ";
         $template = LANG.$language."/LC_MESSAGES/";

         set_language($language);

         $messages = array();
         $untranslated = 0;

         foreach ($keys as $key) {
            if ($messages[$key]) {
               continue;
            } elseif ($translation = _($key)) {
               $messages[$key] = $translation;
            } else {
               $untranslated++;
            }
         }

         foreach ($plurals as $key) {
            if ($messages[$key]) {
               continue;
            } elseif ($translation = ngettext(singularize($key), $key, 2)) {
               $messages[$key] = $translation;
            } else {
               $untranslated++;
            }
         }

         if ($messages) {
            $target = WEBROOT.JAVASCRIPTS."translations/$language.js";

            $file = fopen($target, 'w');
            fputs($file, "Translation = {\n");

            $lines = array();
            foreach ($messages as $key => $translation) {
               $key = str_replace('"', '&quot;', $key);
               $translation = str_replace('"', '&quot;', $translation);
               $lines[] = "  \"$key\": \"$translation\"";
            }

            file_put_contents($target,
               "var Language = '$language';\n" .
               "var Translations = {\n".implode(",\n", $lines)."\n};\n"
            );
         }

         $texts = array();
         if ($translated = count($messages)) {
            $texts[] = pluralize($translated, 'translated message', null, false);
         }

         if ($untranslated) {
            $texts[] = pluralize($untranslated, 'untranslated message', null, false);
         }

         if ($texts) {
            print implode(', ', $texts).".\n";
         } else {
            print "no messages.\n";
         }
      }
   }

   print "\n";

?>
