<?

   $_CONFIG['database'] = array(
      #
      # SQLite example:
      #
      #/*
      'sqlite' => array(
         'driver'   => 'sqlite',

         # Specify different databases for environments
         'production'  => array('database' => DB.'production.db'),
         'development' => array('database' => DB.'development.db'),
         'test'        => array('database' => DB.'test.db'),
      ),
      #*/

      #
      # MySQL example:
      #
      /*
      'mysql' => array(
         'driver'   => 'mysql',
         'hostname' => 'localhost',
         'username' => '',
         'password' => '',
      
         # Specify different databases for environments
         'production'  => array('database' => 'NAME_production'),
         'development' => array('database' => 'NAME_development'),
         'test'        => array('database' => 'NAME_test'),
      ),
      */
   );

?>
