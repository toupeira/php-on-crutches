<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Get object property or array key
   function getf(&$object, $key) {
      if (is_object($object)) {
         return $object->$key;
      } else {
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

   # Shift a value from the array and complain if it's empty
   function array_shift_arg(&$array, $message="Too few arguments") {
      if (empty($array)) {
         throw new ApplicationError($message);
      } else {
         return array_shift($array);
      }
   }

   # Convert an array into a string
   function array_to_str($array) {
      return str_replace("\n", "", var_export($array, true));
   }

?>
