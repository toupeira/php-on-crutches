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
      return ucwords(humanize($text));
   }

   function camelize($text) {
      return str_replace(' ', '', ucwords(str_replace('_', ' ', $text)));
   }

   function underscore($text) {
      return strtolower(preg_replace('/([a-z]) ?([A-Z])/', '\1_\2', $text));
   }

?>
