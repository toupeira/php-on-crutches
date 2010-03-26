<?

   $_CONFIG['routes'] = array(
      ':controller/:action/*id' => array(
         'controller' => 'pages'
      ),

      '*id' => array(
         'controller' => 'pages', 'action' => 'show'
      ),
   );

?>
