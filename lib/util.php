<?# $Id$ ?>
<?

	class Object
	{
		function __get($key) {
			$getter = "get_$key";
         return $this->$getter();
		}

		function __set($key, $value) {
			$setter = "set_$key";
			$this->$setter($value);
			return $this;
		}
	}

	function any() {
		foreach (func_get_args() as $arg) {
			if ($arg) {
				return $arg;
			}
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

	function run($command) {
		log_debug("Running '$command'");
		exec($command, $output, $code);
		return ($code === 0);
	}

   function tempfile() {
      $file = tempnam(sys_get_temp_dir(), 'phpcrutch.');
      register_shutdown_function(unlink, $file);
      return $file;
   }

	class ApplicationError extends Exception {};

	function raise($message) {
		if (is_object($GLOBALS['logger'])) {
			log_error("$message");
		}
		throw new ApplicationError($message);
	}

?>
