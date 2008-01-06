<?
/*
  PHP on Crutches - Copyright (c) 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License.

  $Id$
*/

  function array_get() {
    $keys = func_get_args();
    $array = array_shift($keys);

    if (count($keys) > 1) {
      $filter = array();
      foreach ($keys as $key) {
        $filter[$key] = $array[$key];
      }
      return $filter;
    } else {
      return $array[$keys[0]];
    }
  }

  function array_find($array, $key, $value) {
    foreach ((array) $array as $object) {
      if ($object->$key == $value) {
        return $object;
      }
    }
  }

  function array_pluck($array, $key, $hash=false) {
    $values = array();
    foreach ((array) $array as $object) {
      if ($value = $object->$key) {
        if ($hash) {
          $values[$value] = $value;
        } else {
          $values[] = $value;
        }
      }
    }

    return $values;
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

?>
