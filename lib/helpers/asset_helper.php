<?# $Id$ ?>
<?

	# Build the path for an asset.
	function asset_path($type, $name) {
		if ($type == 'css') {
			$file = STYLESHEETS.$name.'.css';
		} elseif ($type == 'js') {
			$file = JAVASCRIPTS.$name.'.js';
		} elseif ($type == 'image') {
			$file = IMAGES.$name;
		} else {
			raise("Invalid asset type $type");
		}

		$path = Dispatcher::$prefix.$file;
		if (file_exists(WEBROOT.$file)) {
			$path .= '?'.filemtime(WEBROOT.$file);
		} else {
			log_error("Asset not found: /$file");
		}

		return $path;
	}

	# Build a stylesheet tag
	function stylesheet_tag($name, $options=null) {
		return tag('link', $options, array(
			'rel' => 'stylesheet', 'type' => 'text/css',
			'href' => asset_path('css', $name)
		));
	}

	# Build a javascript tag
	function javascript_tag($name, $options=null) {
		return content_tag('script', null, $options, array(
			'type' => 'text/javascript',
			'src' => asset_path('js', $name)
		));
	}

	# Build an image tag
	function image_tag($name, $options=null) {
		return tag('img', $options, array(
			'src' => asset_path('image', $name), 'alt' => ''
		));
	}

?>
