<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Return the first value of an array
   function first($array) {
      return $array[0];
   }

   # Return the first value of an array
   function last($array) {
      return $array[count($array) - 1];
   }

   # Get object property or array key
   function getf(&$object, $key=null) {
      if (is_object($object)) {
         return $object->$key;
      } elseif (is_array($object)) {
         return $object[$key];
      } else {
         return null;
      }
   }

   # Set object property or array key
   function setf(&$object, $key, $value) {
      if (is_object($object)) {
         return $object->$key = $value;
      } elseif (is_array($object)) {
         if ($value === null) {
            unset($object[$key]);
         } else {
            return $object[$key] = $value;
         }
      } else {
         throw new TypeError($object);
      }
   }

   # Convert an array into a string
   function array_to_str(array $array) {
      return preg_replace('/\s+/', ' ', var_export($array, true));
   }

   # Get one or more key/value pairs from an array
   function array_get($array, $keys=null) {
      if (!is_array($keys) and !is_null($keys)) {
         $keys = array_slice(func_get_args(), 1);
      }

      $filter = array();
      foreach ((array) $keys as $key) {
         $filter[$key] = getf($array, $key);
      }

      return $filter;
   }

   # Find an object by property value
   function array_find($array, $key, $value) {
      if ($array instanceof QuerySet) {
         $array = $array->objects;
      }

      foreach ((array) $array as $object) {
         if (getf($object, $key) == $value) {
            return $object;
         }
      }
   }

   # Find all matching values
   function array_find_all($array, $key, $value) {
      if ($array instanceof QuerySet) {
         $array = $array->objects;
      }

      $objects = array();
      foreach ((array) $array as $object) {
         if (getf($object, $key) == $value) {
            $objects[] = $object;
         }
      }

      return $objects;
   }

   # Find all matching values by regular expression
   function array_grep($array, $key, $pattern=null) {
      if ($array instanceof QuerySet) {
         $array = $array->objects;
      }

      if (is_null($pattern)) {
         $pattern = $key;
         $key = null;
      }

      $objects = array();
      foreach ((array) $array as $object) {
         if ($key) {
            $value = getf($object, $key);
         } else {
            $value = $object;
         }

         if (preg_match("#$pattern#i", (string) $value)) {
            $objects[] = $object;
         }
      }

      return $objects;
   }

   # Collect the given array keys or object properties from each value
   function array_pluck($array, $key, $hash=false) {
      if ($array instanceof QuerySet) {
         $array = $array->objects;
      }

      $values = array();
      foreach ($array as $object) {
         $value = getf($object, $key);
         if ($hash) {
            $values[$value] = $value;
         } else {
            $values[] = $value;
         }
      }

      return $values;
   }

   # Delete one or more keys from an array and return the values
   function array_delete(array &$array=null, $keys) {
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
   function array_remove(array &$array, $values) {
      $removed = false;

      if (is_array($values)) {
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
      } else {
         foreach ((array) $array as $key => $value) {
            if ($value == $values) {
               unset($array[$key]);
               return true;
            }
         }
      }

      return $removed;
   }

   # Return an array without one or more values
   function array_without(array $array, $values) {
      array_remove($array, $values);
      return $array;
   }

   function array_update(array &$array) {
      $args = array_slice(func_get_args(), 1);
      array_unshift($args, $array);
      return $array = call_user_func_array('array_merge', $args);
   }

   # Call a method on each object
   function array_collect($objects, $method, $args=null) {
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
   function array_shift_arg(array &$array, $message="Too few arguments") {
      if (empty($array)) {
         throw new ApplicationError($message);
      } else {
         return array_shift($array);
      }
   }

   # Return a sorted array
   function sorted(array $array, $reverse=false) {
      if (isset($array[0])) {
         sort($array);
      } elseif (!empty($array)) {
         ksort($array);
      }

      return $reverse ? array_reverse($array) : $array;
   }

   function sort_by(array &$array, $key, $reverse=false) {
      if (!preg_match('/^\w+$/i', $key)) {
         throw new ValueError("Invalid key '$key'");
      }

      $reverse = ($reverse ? -1 : 1);
      return uasort($array,
         proc("$reverse * compare(getf(\$a, '$key'), getf(\$b, '$key'))", 2));
   }

   function compare($a, $b) {
      if ($a == $b) {
         return 0;
      } elseif (is_numeric($a) and is_numeric($b)) {
         return ($a < $b ? -1 : 1);
      } else {
         return strcmp($a, $b);
      }
   }

?>
