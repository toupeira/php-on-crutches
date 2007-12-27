<?# $Id$ ?>
<?

  function messages($msg, $keys=null) {
    $messages = '';

    if (is_array($msg)) {
      foreach ($msg as $key => $message) {
        if (is_array($keys) and !in_array($key, $keys)) {
          continue;
        } else {
          $message = preg_replace('/\[\[([^]]+)\]\]/', '<code>$1</code>', $message);
          $messages .= content_tag('div', $message, array(
            'class' => "message $key"
          ));
        }
      }
    }

    return $messages;
  }

?>
