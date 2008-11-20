<?

   # Revert magic quotes if enabled, adopted from http://nyphp.org/phundamentals/storingretrieving.php
   function fix_magic_quotes($var=null) {
      # disable magic_quotes_runtime
      set_magic_quotes_runtime(0);

      # if magic_quotes_gpc is disabled, we're already done
      if (!get_magic_quotes_gpc()) {
         return;
      }

      if (is_null($var)) {
         # if no var is specified, fix all affected superglobals

         # workaround because magic_quotes does not change $_SERVER['argv']
         $argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : null; 

         # fix all affected arrays
         foreach (array('_ENV', '_REQUEST', '_GET', '_POST', '_COOKIE', '_SERVER') as $var) {
            if (is_array($GLOBALS[$var])) {
               $GLOBALS[$var] = fix_magic_quotes($GLOBALS[$var]);
            }
         }

         $_SERVER['argv'] = $argv;

         # turn off magic quotes, this is so scripts which
         # are sensitive to the setting will work correctly
         ini_set('magic_quotes_gpc', 0);

         return true;

      } elseif (is_array($var)) {
         # if var is an array, fix each element
         foreach ($var as $key => $val) {
            $var[$key] = fix_magic_quotes($val);
         }

         return $var;

      } elseif (is_string($var)) {
         # if var is a string, strip slashes
         return stripslashes($var);

      } else {
         # otherwise ignore
         return $var;
      }
   }

?>
