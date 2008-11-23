<?# $Id$ ?>
<?

   $fixtures = array(
      'admin' => array(
         'id'               => 1,
         'name'             => 'Mr. Admin',
         'email'            => 'admin@example.com',
         'username'         => 'admin',
         'crypted_password' => '3601b1b00e3ed46e801c158c4bd9518d04b0bfb3', # admin123
         'salt'             => 'c83f1f3e97b6a84260308454fc27a8f25881e6d0',
         'admin'            => 1,
      ),

      'user' => array(
         'id'               => 2,
         'name'             => 'Ms. Alice',
         'email'            => 'alice@example.com',
         'username'         => 'alice',
         'crypted_password' => 'ecfaab51154db857c5ecf7c92ec803aad4823f30', # alice123
         'salt'             => '321d3a6de1d6876ce8a8b9343816154b35b97410',
         'admin'            => 0,
      ),

      'bob' => array(
         'id'               => 3,
         'name'             => 'Mr. Bob',
         'email'            => 'bob@example.com',
         'username'         => 'bob',
         'crypted_password' => 'df1851d3ab89e758ca8a0e63bf71d0a692e6d187', # bob123
         'salt'             => 'dd9127587b27ac809754c1e2c6134ee67fcb1ac9',
         'admin'            => 0,
      ),
   );

?>
