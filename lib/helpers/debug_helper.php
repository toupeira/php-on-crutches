<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Dump a value
   function dump($value) {
      ob_start();
      print_r($value);
      $output = htmlspecialchars(ob_get_clean());
      if (is_array($value)) {
         $output = implode("\n", array_slice(explode("\n", $output), 1));
      }

      return "<pre>$output</pre>";
   }

?>
