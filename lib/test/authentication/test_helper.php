<?

   config_set('auth_model', User);
   config_set('auth_controller', UsersController);

   class User extends AuthenticationModel {}
   class UserMapper extends DatabaseMapper {}
   class UsersController extends AuthenticationController {
      protected $_layout = '';
   }

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
