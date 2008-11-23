<?# $Id$ ?>
<?

   set_language('C');

   Router::clear();
   Router::add(array(
      ':controller/:action/*id' => array(
         'controller' => 'pages'
      ),
   ));

   $GLOBALS['_CONFIG']['database'] = array(
      'default' => array(
         'driver'   => 'sqlite',
         'database' => ':memory:',
      ),
   );

   DB()->execute(file_get_contents(
      LIB.'test/framework/fixtures/schema.sql'
   ));

?>
