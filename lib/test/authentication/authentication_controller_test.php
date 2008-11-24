<?# $Id$ ?>
<?

   class UsersControllerTest extends ControllerTestCase
   {
      function setup_controller() {
         logout();
      }

      function test_login() {
         $this->get('login');
         $this->assertTemplate('login');
      }

      function test_login_logged_in() {
         login('user');
         $this->get('login');
         $this->assertTemplate('login');
      }

      function test_login_post() {
         $this->post('login', array(
            'username' => 'bob',
            'password' => 'bob123',
         ));

         $this->assertRedirect('');
         $this->assertEqual(3, User::current()->id);
         $this->assertEqual(3, $this->session['auth_id']);
      }

      function test_login_post_invalid() {
         $this->post('login', array(
            'username' => 'bob',
            'password' => 'bob',
         ));
         
         $this->assertTemplate('login');
         $this->assertMessage('error');
         $this->assertNull(User::current());
         $this->assertNull($this->session['auth_id']);
      }

      function test_logout() {
         login('admin');
         $this->assertEqual(1, User::current()->id);

         $this->get('logout');

         $this->assertRedirect(':/login');
         $this->assertNull(User::current());
         $this->assertNull($this->session['auth_id']);
      }

      function test_index() {
         login('admin');
         $this->get('index');

         $this->assertTemplate('index');
         $this->assertAssigns(array(
            'users' => QuerySet,
         ));
         $this->assertCount(3, $this->assigns('users'));
      }

      function test_index_unauthorized() {
         login('user');
         $this->get('index');

         $this->assertRedirect('');
      }

      function test_signup() {
         $this->get('signup');

         $this->assertTemplate('create');
         $this->assertAssigns(array(
            'user' => User,
         ));
      }

      function test_signup_post() {
         $count = DB(User)->count();

         $this->post('signup', array('user' => array(
            'name'                  => 'Sample User',
            'email'                 => 'foo@test.com',
            'username'              => 'foo',
            'password'              => 'foo123',
            'password_confirmation' => 'foo123',
         )));

         $this->assertRedirect(':/login');
         $this->assertMessage('info');

         $this->assertEqual($count + 1, DB(User)->count());
      }

      function test_signup_post_invalid() {
         $count = DB(User)->count();

         $this->post('signup', array('user' => array(
            'name' => '',
         )));

         $this->assertTemplate('create');
         $this->assertMessage('error');
         $this->assertEqual($count, DB(User)->count());
      }

      function test_edit() {
         login('user');

         $this->get('edit/2');

         $this->assertTemplate('edit');
         $this->assertEqual(2, $this->assigns('user')->id);
      }

      function test_edit_unauthorized() {
         login('user');

         $this->get('edit/1');

         $this->assertMessage('error');
         $this->assertRedirect(':');
      }

      function test_edit_invalid() {
         login('admin');

         $this->get('edit/666');

         $this->assertRedirect(':');
         $this->assertMessage('error');
      }

      function test_edit_post() {
         login('admin');

         $this->post('edit/1', array('user' => array(
            'name' => 'foo',
         )));

         $this->assertRedirect(':');
         $this->assertMessage('info');
         $this->assertEqual('foo', DB(User)->find(1)->name);
      }

      function test_edit_post_unauthorized() {
         login('user');

         $this->post('edit/1', array('user' => array(
            'name' => 'foo',
         )));

         $this->assertRedirect(':');
         $this->assertMessage('error');
         $this->assertEqual('Mr. Admin', DB(User)->find(1)->name);

         $this->post('edit/2', array('user' => array(
            'name' => 'foo',
         )));

         $this->assertRedirect(':');
         $this->assertEqual('foo', DB(User)->find(2)->name);
      }

      function test_edit_post_invalid() {
         login('admin');

         $this->post('edit/1', array('user' => array(
            'name' => '',
         )));

         $this->assertTemplate('edit');
         $this->assertMessage('error');
         $this->assertEqual('Mr. Admin', DB(User)->find(1)->name);
      }

      function test_destroy() {
         $this->get('destroy/1');
         $this->assertRedirect(':');
      }

      function test_destroy_post() {
         $count = DB(User)->count();

         login('admin');

         $this->post('destroy/1');

         $this->assertRedirect(':');
         $this->assertMessage('info');
         $this->assertEqual(null, DB(User)->find(1));

         $this->assertEqual($count - 1, DB(User)->count());
      }

      function test_destroy_post_self() {
         $count = DB(User)->count();

         login('user');

         $this->post('destroy/2');

         $this->assertRedirect(':');
         $this->assertMessage('info');
         $this->assertEqual(null, DB(User)->find(2));

         $this->assertEqual($count - 1, DB(User)->count());
      }

      function test_destroy_post_unauthorized() {
         $count = DB(User)->count();

         login('user');

         foreach (array(1, 3) as $id) {
            $this->post("destroy/$id");
            $this->assertRedirect(':');
            $this->assertMessage('error');
         }

         $this->assertEqual($count, DB(User)->count());
      }

      function test_destroy_post_invalid() {
         $count = DB(User)->count();

         login('admin');

         $this->post('destroy/666');

         $this->assertRedirect(':');
         $this->assertMessage('error');

         $this->assertEqual($count, DB(User)->count());
      }
   }

?>
