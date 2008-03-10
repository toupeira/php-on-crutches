<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Helper functions for handling associative arrays
   abstract class hash
   {
      # Get one or more key values from an array
      static function get(&$array) {
         $keys = array_slice(func_get_args(), 1);

         if (is_array($keys[0])) {
            $keys = $keys[0];
         } elseif (count($keys) == 1) {
            $keys = $keys[0];
         }

         if (is_array($keys)) {
            $filter = array();
            foreach ($keys as $key) {
               $filter[$key] = $array[$key];
            }
            return $filter;
         } else {
            return $array[$keys];
         }
      }

      # Delete and return one or more keys from an array
      static function delete(&$array, $keys) {
         if (func_num_args() > 2) {
            $keys = array_slice(func_get_args(), 1);
         }

         if (is_array($keys)) {
            $values = array();
            foreach ($keys as $key) {
               if ($value = $array[$key]) {
                  $values[] = $value;
                  unset($array[$key]);
               }
            }

            return $values;
         } else {
            if ($value = $array[$keys]) {
               unset($array[$keys]);
               return $value;
            }
         }
      }
   }

?>
