<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Convert an array into a string
   function array_to_str($array) {
      return str_replace("\n", "", var_export($array, true));
   }

   # Get object property or array key
   function getf(&$object, $key=null) {
      if (is_null($key)) {
         return $object;
      } elseif (is_object($object)) {
         return $object->$key;
      } elseif (is_array($object)) {
         return $object[$key];
      }
   }

   # Set object property or array key
   function setf(&$object, $key, $value) {
      if (is_object($object)) {
         return $object->$key = $value;
      } elseif ($value === null) {
         unset ($object[$key]);
      } else {
         return $object[$key] = $value;
      }
   }

   # Get one or more key/value pairs from an array
   function array_get(&$array, $keys=null) {
      if (!is_array($keys) and !is_null($keys)) {
         $keys = array_slice(func_get_args(), 1);
      }

      $filter = array();
      foreach ((array) $keys as $key) {
         $filter[$key] = $array[$key];
      }

      return $filter;
   }

   # Find an object by property value
   function array_find(&$array, $key, $value) {
      foreach ((array) $array as $object) {
         if (getf($object, $key) == $value) {
            return $object;
         }
      }
   }

   # Find all matching values
   function array_find_all(&$array, $key, $value) {
      $objects = array();
      foreach ((array) $array as $object) {
         if (getf($object, $key) == $value) {
            $objects[] = $object;
         }
      }

      return $objects;
   }

   # Find all matching values by regular expression
   function array_grep(&$array, $key, $pattern=null) {
      if (is_null($pattern)) {
         $pattern = $key;
         $key = null;
      }

      $objects = array();
      foreach ((array) $array as $object) {
         if (preg_match("#$pattern#", getf($object, $key))) {
            $objects[] = $object;
         }
      }

      return $objects;
   }

   # Collect the given array keys or object properties from each value
   function array_pluck(&$array, $key, $hash=false) {
      $values = array();
      foreach ((array) $array as $object) {
         if ($value = getf($object, $key)) {
            if ($hash) {
               $values[$value] = $value;
            } else {
               $values[] = $value;
            }
         }
      }

      return $values;
   }

   # Delete one or more keys from an array
   function array_delete(&$array, $keys) {
      if (!is_array($array)) {
         return;
      }

      if (func_num_args() > 2) {
         $keys = array_slice(func_get_args(), 1);
      }

      if (is_array($keys)) {
         $values = array();
         foreach ($keys as $key) {
            if (array_key_exists($key, $array)) {
               $values[] = $array[$key];
               unset($array[$key]);
            }
         }

         return $values;
      } else {
         if (array_key_exists($keys, $array)) {
            $value = $array[$keys];
            unset($array[$keys]);
            return $value;
         }
      }
   }

   # Delete one or more values from an array
   function array_remove(&$array, $values) {
      $values = (array) $values;
      $removed = false;

      foreach ((array) $array as $key => $value) {
         if (in_array($value, $values)) {
            unset($array[$key]);
            array_shift($values);
            if (empty($values)) {
               return true;
            }
            $removed = true;
         }
      }

      return $removed;
   }

   # Call a method on each object
   function array_send(&$objects, $method, $args=null) {
      if (!is_array($args)) {
         $args = array_slice(func_get_args(), 2);
      }

      $values = array();
      foreach ($objects as $object) {
         $values[] = call_user_func_array(array($object, $method), $args);
      }

      return $values;
   }

   # Shift a value from the array and complain if it's empty
   function array_shift_arg(&$array, $message="Too few arguments") {
      if (empty($array)) {
         throw new ApplicationError($message);
      } else {
         return array_shift($array);
      }
   }

?>
