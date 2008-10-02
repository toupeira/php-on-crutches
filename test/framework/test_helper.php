<?# $Id$ ?>
<?

   Router::clear();
   Router::add(array(
      ':controller/:action/*id' => array(
         'controller' => 'pages'
      ),
   ));

?>
