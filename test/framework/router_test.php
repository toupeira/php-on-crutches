<?# $Id$ ?>
<?

   class RouterTest extends TestCase
   {
      function setup() {
         Router::clear();
         Router::add(array(
            # Fixed route
            'signup' => array('controller' => 'users', 'action' => 'signup'),

            # Fixed controller route
            'files/:action/:id' => array('controller' => 'files'),

            # Route with required parameters
            'users/required/:action!/:id' => array('controller' => 'users'),

            # Wildcard route
            'browse/*path' => array('controller' => 'pages', 'action' => 'browse'),

            # Route with format specification
            ':controller/*id' => array('controller' => 'home', 'action' => 'show', 'id' => '/\d+/'),

            # Generic route with default controller
            ':controller/:action/*id' => array('controller' => 'home'),
         ));
      }

      function test_default_route() {
         $this->assertGenerates('', array('controller' => 'home'));
         $this->assertRouting('', array('controller' => 'home', 'action' => 'index'));

         $this->assertGenerates('home/edit', array('action' => 'edit'));
         $this->assertRouting('home/edit', array('controller' => 'home', 'action' => 'edit'));
         $this->assertRouting('foo/bar/3', array('controller' => 'foo', 'action' => 'bar', 'id' => 3));
         $this->assertRouting('foo/bar/3/9/81/6561', array('controller' => 'foo', 'action' => 'bar', 'id' => '3/9/81/6561'));
      }

      function test_route_with_fixed_path() {
         $this->assertRouting('signup', array('controller' => 'users', 'action' => 'signup'));
      }

      function test_route_with_fixed_controller() {
         $this->assertRouting('files', array('controller' => 'files', 'action' => 'index'));
         $this->assertRouting('files/create', array('controller' => 'files', 'action' => 'create'));
      }

      function test_route_with_required_parameter() {
         $this->assertGenerates('users', array('controller' => 'users'));
         $this->assertRouting('users/required/index', array('controller' => 'users', 'action' => 'index'));
         $this->assertRouting('users/required/login', array('controller' => 'users', 'action' => 'login'));
         $this->assertRouting('users/required/edit/3', array('controller' => 'users', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_wildcard_parameter() {
         $this->assertRouting('browse', array('controller' => 'pages', 'action' => 'browse'));
         $this->assertRouting('browse/foo', array('controller' => 'pages', 'action' => 'browse', 'path' => 'foo'));
         $this->assertRouting('browse/foo/bar/baz', array('controller' => 'pages', 'action' => 'browse', 'path' => 'foo/bar/baz'));
      }

      function test_route_with_format_specification() {
         $this->assertRouting('posts/123', array('controller' => 'posts', 'action' => 'show', 'id' => '123'));
         $this->assertGenerates('posts/show/abc', array('controller' => 'posts', 'action' => 'show', 'id' => 'abc'));
         $this->assertRecognizes(array('controller' => 'posts', 'action' => 'abc'), 'posts/abc');
      }

      function test_route_with_query_string() {
         $this->assertRouting('files?foo%3Dbar=foo+bar', array('controller' => 'files', 'action' => 'index', 'foo=bar' => 'foo bar'));
         $this->assertRouting('foo/bar/23?foo%3Dbar=foo+bar', array('controller' => 'foo', 'action' => 'bar', 'id' => 23, 'foo=bar' => 'foo bar'));
         $this->assertRouting('browse?foo%3Dbar=foo+bar', array('controller' => 'pages', 'action' => 'browse', 'foo=bar' => 'foo bar'));
      }
   }

?>
