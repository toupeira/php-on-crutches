<?

   $_CONFIG['routes'] = array(
      ':controller/*id' => array(
         'controller' => 'pages', 'action' => 'show', 'id' => '/\d.*/'
      ),

      ':controller/:action/*id' => array(
         'controller' => 'pages'
      ),
   );

?>
