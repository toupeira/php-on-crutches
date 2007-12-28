<?# $Id$ ?>
<?

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

  function array_get($array, $key) {
    return $array[$key];
  }

  function array_find($array, $key, $value) {
    foreach ($array as $object) {
      if ($object->$key == $value) {
        return $object;
      }
    }
  }

  function array_pluck($array, $key, $hash=false) {
    $values = array();
    foreach ($array as $object) {
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

?>
