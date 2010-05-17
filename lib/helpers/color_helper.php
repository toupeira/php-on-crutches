<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Generate random colors which are evenly distributed around all hues
   function random_colors($count=10, $saturation=50, $value=80) {
      if ($count <= 0) {
         throw new ValueError($count);
      }

      $colors = array();
      $step = 360 / $count;

      for ($hue = 0; $hue <= 360; $hue += $step) {
         $colors[] = rgb2hex(hsv2rgb($hue, $saturation, $value));
      }

      return $colors;
   }

   # Generate a random hex color for a given string which always stays the same for the given input
   function random_color($string, $count=10, $saturation=50, $value=80) {
      static $_colors;
      static $_hexcolors;
      static $_count;

      if (is_null($_colors) or (!is_null($_count) and $_count != $count)) {
         $_count = $count;
         $_colors = random_colors($count, $saturation, $value);
         $_hexcolors = array();
      }

      if (!array_key_exists($string, $_hexcolors)) {
         srand(crc32($string));
         $_hexcolors[$string] = $_colors[rand(0, $_count)];
      }

      return $_hexcolors[$string];
   }

   # Convert RGB colors (array(0-255, 0-255, 0-255)) to hex string ("#xxxxxx")
   function rgb2hex($red, $green=null, $blue=null) {
      if (is_array($red)) {
         list($red, $green, $blue) = $red;
      }

      return sprintf("#%02x%02x%02x", $red, $green, $blue);
   }

   # Convert RGB colors to HSV values (array(0-360, 0-100, 0-100))
   # Adapted from the comments at http://php.net/manual/en/function.imagecolorsforindex.php
   function rgb2hsv($red, $green=null, $blue=null) {
      if (is_array($red)) {
         list($red, $green, $blue) = $red;
      }

      $min = min($red, $green, $blue);
      $max = max($red, $green, $blue);
      $delta  = $max - $min;

      $value = $max / 255;

      if ($delta == 0) {
         $hue = 0;
         $saturation = 0;
      } else {
         $saturation = $delta / $max;
         $del_r = ((($max - $red) / 6) + ($delta / 2)) / $delta;
         $del_g = ((($max - $green) / 6) + ($delta / 2)) / $delta;
         $del_b = ((($max - $blue) / 6) + ($delta / 2)) / $delta;

         if ($red == $max){
            $hue = $del_b - $del_g;
         } else if ($green == $max) {
            $hue = (1 / 3) + $del_r - $del_b;
         } else if ($blue == $max) {
            $hue = (2 / 3) + $del_g - $del_r;
         }
       
         if ($hue < 0) $hue++;
         if ($hue > 1) $hue--;
      }

      return array(
         round($hue * 360),
         round($saturation * 100),
         round($value * 100),
      );
   }

   # Convert HSV values to RGB colors
   # Adapted from the comments at http://php.net/manual/en/function.imagecolorallocate.php
   function hsv2rgb($hue, $saturation, $value) {
      if (is_array($hue)) {
         list($hue, $saturation, $value) = $hue;
      }

      if ($saturation == 0) {
         $value = ($value / 100 * 255);
         return array($value , $value, $value);
      } else {
         $hue = ($hue %= 360) / 60;
         $value /= 100;
         $saturation /= 100;

         $i = floor($hue);
         $f = $hue - $i;
         $q[0] = $q[1] = $value * (1 - $saturation);
         $q[2] = $value * (1 - $saturation * (1 - $f));
         $q[3] = $q[4] = $value;
         $q[5] = $value * (1 - $saturation * $f);

         //return(array($q[($i + 4) % 5], $q[($i + 2) % 5], $q[$i % 5]));
         return array(
            round($q[($i + 4) % 6] * 255),
            round($q[($i + 2) % 6] * 255),
            round($q[$i % 6] * 255),
         );
      }
   } 

?>
