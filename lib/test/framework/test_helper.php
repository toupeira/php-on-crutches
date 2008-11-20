<?# $Id$ ?>
<?

   set_language('C');

   Router::clear();
   Router::add(array(
      ':controller/:action/*id' => array(
         'controller' => 'pages'
      ),
   ));

?>
