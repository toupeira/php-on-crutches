<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Get values from an array by one or more keys
   function array_get(&$array) {
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

   # Find an object by property value
   function array_find(&$array, $key, $value) {
      foreach ((array) $array as $object) {
         if ($object->$key == $value) {
            return $object;
         }
      }
   }

   # Filter all values from an array which match the pattern
   function array_grep(&$array, $key, $pattern) {
      $values = array();
      foreach ((array) $array as $value) {
         if (preg_match("/$pattern/", $value)) {
            $values[] = $value;
         }
      }

      return $values;
   }

   # Collect the given array keys or object properties from each value
   function array_pluck(&$array, $key, $hash=false) {
      $values = array();
      foreach ((array) $array as $object) {
         if ($value = (is_object($object) ? $object->$key : $object[$key])) {
            if ($hash) {
               $values[$value] = $value;
            } else {
               $values[] = $value;
            }
         }
      }

      return $values;
   }

   # Execute a method on each object
   function array_map_method($method, &$objects) {
      foreach ($objects as $object) {
         $object->$method();
      }
   }

   # Delete one or more keys from an array
   function array_delete(&$array, $keys) {
      if (is_array($keys)) {
         foreach ($keys as $key) {
            if ($value = $array[$key]) {
               $values[] = $array[$key];
               unset($array[$key]);
            }
         }

         return $values;
      } else {
         if ($value = $array[$keys]) {
            unset($array[$keys]);
            return $value;
         }

         return null;
      }
   }

   # Delete one or more values from an array
   function array_remove(&$array, $values) {
      if (is_array($values)) {
         foreach ($array as $key => $value) {
            if (in_array($value, $values)) {
               unset($array[$key]);
            }
         }
      } else {
         foreach ($array as $key => $value) {
            if ($value == $values) {
               unset($array[$key]);
               return $value;
            }
         }
      }
   }

   # Shift a value from the array and complain if it's empty
   function array_shift_arg(&$array, $message="Too few arguments") {
      if (empty($array)) {
         raise($message);
      } else {
         return array_shift($array);
      }
   }

   # Convert an array into a string
   function array_to_str($array) {
      return str_replace("\n", "", var_export($array, true));
   }

?>
