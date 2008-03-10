<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Helper functions for object collections and nested arrays.
   abstract class set
   {
      # Find the first matching value
      static function find(&$array, $key, $value) {
         foreach ((array) $array as $object) {
            if (getf($object, $key) == $value) {
               return $object;
            }
         }
      }

      # Find all matching values
      static function find_all(&$array, $key, $value) {
         $objects = array();
         foreach ((array) $array as $object) {
            if (getf($object, $key) == $value) {
               $objects[] = $object;
            }
         }

         return $objects;
      }

      # Find all matching values by regular expression
      static function grep(&$array, $key, $pattern) {
         $objects = array();
         foreach ((array) $array as $object) {
            if (preg_match("#$pattern#", getf($object, $key))) {
               $objects[] = $object;
            }
         }

         return $objects;
      }

      # Collect the given key or property from each value
      static function pluck(&$array, $key, $hash=false) {
         $values = array();
         foreach ((array) $array as $object) {
            if ($value = getf($object, $key)) {
               if ($hash === true) {
                  $values[$value] = $value;
               } else {
                  $values[] = $value;
               }
            }
         }

         return $values;
      }

      # Remove one or more values from an array
      static function remove(&$array, $values) {
         $values = (array) $values;

         foreach ((array) $array as $key => $value) {
            if (in_array($value, $values)) {
               unset($array[$key]);
               array_shift($values);
               if (empty($values)) {
                  return $value;
               }
            }
         }
      }

      # Call a method on each object
      static function send(&$objects, $method, $args=null) {
         if (!is_array($args)) {
            $args = array_slice(func_get_args(), 2);
         }

         $values = array();
         foreach ($objects as $object) {
            $values[] = call_user_func_array(array($object, $method), $args);
         }

         return $values;
      }
   }

?>
