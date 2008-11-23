<?# $Id$ ?>
<?

   class AuthenticationModelTest extends ModelTestCase
   {
      function test_authenticate() {
         $this->assertEqual('Mr. Bob', User::authenticate('bob', 'bob123')->name);

         $this->assertNull(User::authenticate('bob', 'bob'));
         $this->assertNull(User::authenticate('foo', 'bar'));
      }

      function test_authenticate_token() {
         $this->assertEqual('Mr. Admin', User::authenticate_token('fe7e395e6148f72bcabe94290a65cf16cef052de1')->name);
         $this->assertEqual('Ms. Alice', User::authenticate_token('7fa45b74d0bce35473318b8033bfd8d977b9f9072')->name);

         $this->assertNull(User::authenticate_token(''));
         $this->assertNull(User::authenticate_token('fe7e395e6148f72bcabe94290a65cf16cef052de'));
         $this->assertNull(User::authenticate_token('fe7e395e6148f72bcabe94230a65cf16cef052de1'));
         $this->assertNull(User::authenticate_token('fe7e395e6148f72bcabe94290a65cf16cef052de2'));
         $this->assertNull(User::authenticate_token('7fa45b74d0bce35473318b8033bfd8d977b9f9071'));
      }

      function test_validation() {
         $user = new User();

         $this->assertFalse($user->is_valid());
         $this->assertCount(4, $user->errors);
         $this->assertHasError($user, 'name');
         $this->assertHasError($user, 'email');
         $this->assertHasError($user, 'username');
         $this->assertHasError($user, 'password');

         $user->name = 'foo';
         $user->email = 'foo';
         $user->username = 'bob';
         $user->password = '123';

         $this->assertFalse($user->is_valid());
         $this->assertCount(4, $user->errors);
         $this->assertHasError($user, 'email');
         $this->assertHasError($user, 'username');
         $this->assertHasError($user, 'password');
         $this->assertHasError($user, 'password_confirmation');

         $user->email = 'foo@foo.com';
         $user->username = 'foo';
         $user->password = '123456';

         $this->assertFalse($user->is_valid());
         $this->assertCount(1, $user->errors);
         $this->assertHasError($user, 'password_confirmation');

         $user->password_confirmation = '123456';

         $this->assertTrue($user->is_valid());
      }

      function test_validation_existing() {
         $user = DB(User)->find(1);

         $this->assertTrue($user->is_valid());

         $user->password = '123';

         $this->assertFalse($user->is_valid());
         $this->assertCount(2, $user->errors);
         $this->assertHasError($user, 'password');
         $this->assertHasError($user, 'password_confirmation');

         $user->password = '123456';
         $user->password_confirmation = '123456';

         $this->assertTrue($user->is_valid());
      }

      function test_get_token() {
         $this->assertEqual('fe7e395e6148f72bcabe94290a65cf16cef052de', DB(User)->find(1)->token);
      }

      function test_encrypt() {
         $user = new User(array('salt' => '123'));
         $this->assertEqual(sha1('--123--foo--'), $user->encrypt('foo'));
      }

      function test_before_validation() {
         $user = new User(array(
            'name'                  => 'foo',
            'email'                 => 'foo@foo.com',
            'username'              => 'foo',
            'password'              => 'foo123',
            'password_confirmation' => 'foo123',
         ));

         $this->assertTrue($user->save());
         $salt = $user->salt;

         $this->assertTrue(preg_match('/^\w{40}$/', $salt));
         $this->assertEqual(sha1("--$salt--foo123--"), $user->crypted_password);

         $user->password = '123456';
         $user->password_confirmation = '123456';

         $this->assertTrue($user->save());
         $this->assertEqual(sha1("--$salt--123456--"), $user->crypted_password);
      }

      function test_before_validation_change_admin() {
         User::login(2);

         $user = DB(User)->find(2);
         $user->admin = true;

         $this->assertTrue($user->save());
         $this->assertFalse(DB(User)->find(2)->admin);

         User::login(1);

         $user = DB(User)->find(2);
         $user->admin = true;

         $this->assertTrue($user->save());
         $this->assertTrue(DB(User)->find(2)->admin);
      }
   }

?>
