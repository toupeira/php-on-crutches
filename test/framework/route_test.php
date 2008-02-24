<?# $Id$ ?>
<?

   class RouteTest extends TestCase
   {
      function setup() {
         Route::clear();
         Route::add(array(
            ''                  => array('index'),
            'pages/:action/:id' => array('pages'),
            'user/:action!/:id' => array('users'),
            'browse/*path'      => array('files', 'browse'),
            ':controller/:action/:id',
         ));
      }

      function test_default_route() {
         $this->assertRouting('', array('controller' => 'index'));
      }

      function test_standard_route() {
         $this->assertRouting('foo', array('controller' => 'foo'));
         $this->assertRouting('foo/create', array('controller' => 'foo', 'action' => 'create'));
         $this->assertRouting('foo/edit/3', array('controller' => 'foo', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_static_controller() {
         $this->assertRouting('pages', array('controller' => 'pages'));
         $this->assertRouting('pages/create', array('controller' => 'pages', 'action' => 'create'));
         $this->assertRouting('pages/edit/3', array('controller' => 'pages', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_required_parameter() {
         $this->assertRouting('user', array('controller' => 'user'));
         $this->assertRouting('user/signup', array('controller' => 'users', 'action' => 'signup'));
         $this->assertRouting('user/edit/3', array('controller' => 'users', 'action' => 'edit', 'id' => 3));
      }

      function test_route_with_wildcard_parameter() {
         $this->assertRouting('browse', array('controller' => 'files', 'action' => 'browse'));
         $this->assertRouting('browse/foo', array('controller' => 'files', 'action' => 'browse', 'path' => 'foo'));
         $this->assertRouting('browse/foo/bar', array('controller' => 'files', 'action' => 'browse', 'path' => 'foo/bar'));
      }
   }

?>
