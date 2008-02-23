<?

   # Taken from http://nyphp.org/phundamentals/storingretrieving.php

   function fix_magic_quotes ($var=null) {
      # if no var is specified, fix all affected superglobals
      if (!isset ($var)) {
         # if magic quotes is enabled
         if (get_magic_quotes_gpc ()) {
            # workaround because magic_quotes does not change $_SERVER['argv']
            $argv = isset($_SERVER['argv']) ? $_SERVER['argv'] : null; 

            # fix all affected arrays
            foreach (array ('_ENV', '_REQUEST', '_GET', '_POST', '_COOKIE', '_SERVER') as $var) {
               $GLOBALS[$var] = fix_magic_quotes ($GLOBALS[$var]);
            }

            $_SERVER['argv'] = $argv;

            # turn off magic quotes, this is so scripts which
            # are sensitive to the setting will work correctly
            ini_set ('magic_quotes_gpc', 0);
         }

         # disable magic_quotes_runtime
         set_magic_quotes_runtime (0);
         return true;
      }

      # if var is an array, fix each element
      if (is_array ($var)) {
         foreach ($var as $key => $val) {
            $var[$key] = fix_magic_quotes ($val);
         }

         return $var;
      }

      # if var is a string, strip slashes
      if (is_string ($var)) {
         return stripslashes ($var);
      }

      # otherwise ignore
      return $var;
   }

?>
