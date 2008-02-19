<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function humanize($text) {
      return ucfirst(str_replace('_', ' ', underscore($text)));
   }

   function titleize($text) {
      return preg_replace_callback('/ ([a-z])/',
         proc('strtoupper(" ".$a[1])'), humanize($text));
   }

   function camelize($text) {
      $text = str_replace('_', ' ', basename(strtolower(trim($text))));
      for ($i = 0; $i < strlen($text); $i++) {
         if ($text[$i] == ' ') {
            $text[$i+1] = strtoupper($text[$i+1]);
         }
      }
      return str_replace(' ', '', ucfirst($text));
   }

   function underscore($text) {
      return strtolower(preg_replace('/([a-z]) ?([A-Z])/', '\1_\2',
         basename(trim($text))));
   }

?>
