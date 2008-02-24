<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

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

   function array_find(&$array, $key, $value) {
      foreach ((array) $array as $object) {
         if ($object->$key == $value) {
            return $object;
         }
      }
   }

   function array_grep(&$array, $key, $pattern) {
      $values = array();
      foreach ((array) $array as $value) {
         if (preg_match("/$pattern/", $value)) {
            $values[] = $value;
         }
      }

      return $values;
   }

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

   function array_map_method($method, &$objects) {
      foreach ($objects as $object) {
         $object->$method();
      }
   }

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

   function array_shift_arg(&$array) {
      if (empty($array)) {
         raise("Too few arguments");
      } else {
         return array_shift($array);
      }
   }

?>
