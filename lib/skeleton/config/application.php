<?

   $_CONFIG['application'] = array(
      'name'            => basename(ROOT),
      'prefix'          => '/',
      'languages'       => array('en'),

      'auth_model'      => null,
      'auth_controller' => null,

      #'mail_sender'     => '',
      #'mail_from'       => '',
      #'mail_from_name'  => '',

      # Change this to improve security
      'secret_key'      => 'c98df57338cb59822da92faa6f668ec5363b57e1',

      'trusted_hosts'   => array(
         '127.0.0.1'
      ),
   );

?>
