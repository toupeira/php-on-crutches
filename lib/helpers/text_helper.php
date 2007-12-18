<?# $Id$ ?>
<?

	function h($text) {
		return htmlentities($text);
	}

	function pluralize($count, $singular, $plural) {
		return $count == 1 ? $singular : $plural;
	}

	function humanize($text) {
		return ucfirst(str_replace('_', ' ', $text));
	}

   function underscore($text) {
		return strtolower(preg_replace('/([a-z])([A-Z])/', '\1_\2', basename($text)));
   }

	function truncate($text, $length=40) {
		if (count($text) > $length) {
			return substr($text, 0, 40)."...";
		} else {
			return $text;
		}
	}

	function br2ln($text) {
		return str_replace("<br />", "\n", $text);
	}

?>
