<?# $Id$ ?>
<?

   set_language('C');

   config_set('debug_controller', true);

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
      LIB.'test/fixtures/schema.sql'
   ));

   class User extends AuthenticationModel {}
   class UserMapper extends DatabaseMapper {}
   class UsersController extends AuthenticationController {
      protected $_layout = '';
   }

   config_set('auth_model', User);
   config_set('auth_controller', UsersController);

   function login($user) {
      if ($id = getf(fixture(User, $user), 'id')) {
         $_SESSION['auth_id'] = $id;
         User::login($id);
      }
   }

   function logout() {
      unset($_SESSION['auth_id']);
      User::logout();
   }

?>
