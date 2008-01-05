<?# $Id$ ?>
<?

  function h($text) {
    return htmlentities($text);
  }

  function pluralize($count, $singular, $plural) {
    return $count == 1 ? "$count $singular" : "$count $plural";
  }

  function humanize($text) {
    return ucfirst(str_replace('_', ' ', underscore($text)));
  }

  function camelize($text) {
    return str_replace(' ', '', humanize($text));
  }

  function underscore($text) {
    return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', basename($text)));
  }

  function truncate($text, $length=40) {
    if (strlen($text) > $length) {
      return substr($text, 0, $length)."...";
    } else {
      return $text;
    }
  }

  function cycle($values) {
    global $_cycle;
    $values = func_get_args();
    $value = $values[intval($_cycle)];
    if (++$_cycle >= count($values)) {
      $_cycle = 0;
    }
    return $value;
  }

  function br2nl($text) {
    return str_replace("<br />", "\n", $text);
  }

?>
