<?# $Id$ ?>
<?

   class RouterTest extends TestCase
   {
      function setup() {
         Router::clear();
         Router::add(array(
            # Fixed controller route
            'files/:action/:id' => array('controller' => 'files'),

            # Route with required parameters
            'user/:action!/:id' => array('controller' => 'users'),

            # Wildcard route
            'browse/*path'      => array('controller' => 'pages', 'action'     => 'browse'),

            # Generic controller route with defaults
            ':controller/:action/:id' => array('controller' => 'home', 'action'     => 'index'),
         ));
      }

      function test_generic_route() {
         $this->assertGenerates('', array('controller' => 'home'));
         $this->assertRouting('', array('controller' => 'home', 'action' => 'index'));

         $this->assertGenerates('home/edit', array('action' => 'edit'));
         $this->assertRouting('home/edit', array('controller' => 'home', 'action' => 'edit'));
         $this->assertRouting('foo/bar/3', array('controller' => 'foo', 'action' => 'bar', 'id' => 3));
      }

      function test_standard_route() {
         $this->assertRouting('foo', array('controller' => 'foo', 'action' => 'index'));
         $this->assertRouting('foo/create', array('controller' => 'foo', 'action' => 'create'));
         $this->assertRouting('foo/edit/3', array('controller' => 'foo', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_static_controller() {
         $this->assertRouting('files', array('controller' => 'files'));
         $this->assertRouting('files/create', array('controller' => 'files', 'action' => 'create'));
         $this->assertRouting('files/edit/3', array('controller' => 'files', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_required_parameter() {
         $this->assertRouting('user', array('controller' => 'user', 'action' => 'index'));
         $this->assertRouting('user/signup', array('controller' => 'users', 'action' => 'signup'));
         $this->assertRouting('user/edit/3', array('controller' => 'users', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_wildcard_parameter() {
         $this->assertRouting('browse', array('controller' => 'pages', 'action' => 'browse'));
         $this->assertRouting('browse/foo', array('controller' => 'pages', 'action' => 'browse', 'path' => 'foo'));
         $this->assertRouting('browse/foo/bar', array('controller' => 'pages', 'action' => 'browse', 'path' => 'foo/bar'));
      }

      function test_route_with_query_string() {
         $this->assertRouting('browse?foo%3Dbar=foo+bar', array('controller' => 'pages', 'action' => 'browse', 'foo=bar' => 'foo bar'));
      }
   }

?>
