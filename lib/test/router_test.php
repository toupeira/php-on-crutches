<?# $Id$ ?>
<?

   class RouterTest extends TestCase
   {
      function setup() {
         Router::clear();
         Router::add(array(
            # Fixed route
            'signup' => array('controller' => 'errors', 'action' => 'signup'),

            # Fixed controller route
            'debug/:action/:id' => array('controller' => 'debug'),

            # Route with required parameters
            'errors/required/:type!/:id' => array('controller' => 'errors', 'action' => 'show'),

            # Wildcard route
            'browse/*path' => array('controller' => 'pages', 'action' => 'browse'),

            # Route with format specification
            ':controller/*id' => array('controller' => 'pages', 'action' => 'show', 'id' => '/\d+/'),

            # Generic route with default controller
            ':controller/:action/*id' => array('controller' => 'pages'),
         ));
      }

      function test_default_route() {
         $this->assertGenerates('', array('controller' => 'pages'));
         $this->assertRouting('', array('controller' => 'pages', 'action' => 'index'));

         $this->assertGenerates('pages/edit', array('action' => 'edit'));
         $this->assertRouting('pages/edit', array('controller' => 'pages', 'action' => 'edit'));
         $this->assertRouting('pages/bar/0', array('controller' => 'pages', 'action' => 'bar', 'id' => '0'));
         $this->assertRouting('pages/bar/3', array('controller' => 'pages', 'action' => 'bar', 'id' => '3'));
         $this->assertRouting('debug/bar/3/9/81/6561', array('controller' => 'debug', 'action' => 'bar', 'id' => '3/9/81/6561'));
      }

      function test_route_with_fixed_path() {
         $this->assertRouting('signup', array('controller' => 'errors', 'action' => 'signup'));
      }

      function test_route_with_fixed_controller() {
         $this->assertRouting('debug', array('controller' => 'debug', 'action' => 'index'));
         $this->assertRouting('debug/create', array('controller' => 'debug', 'action' => 'create'));
      }

      function test_route_with_required_parameter() {
         $this->assertGenerates('errors', array('controller' => 'errors'));
         $this->assertRouting('errors/required/index', array('controller' => 'errors', 'type' => 'index', 'action' => 'show'));
         $this->assertRouting('errors/required/login', array('controller' => 'errors', 'type' => 'login', 'action' => 'show'));
         $this->assertRouting('errors/required/edit/3', array('controller' => 'errors', 'type' => 'edit', 'action' => 'show', 'id' => '3'));
      }

      function test_route_with_wildcard_parameter() {
         $this->assertRouting('browse', array('controller' => 'pages', 'action' => 'browse'));
         $this->assertRouting('browse/errors', array('controller' => 'pages', 'action' => 'browse', 'path' => 'errors'));
         $this->assertRouting('browse/errors/bar/baz', array('controller' => 'pages', 'action' => 'browse', 'path' => 'errors/bar/baz'));
      }

      function test_route_with_format_specification() {
         $this->assertRouting('pages/123', array('controller' => 'pages', 'action' => 'show', 'id' => '123'));
         $this->assertGenerates('pages/show/abc', array('controller' => 'pages', 'action' => 'show', 'id' => 'abc'));
         $this->assertRecognizes(array('controller' => 'pages', 'action' => 'abc'), 'pages/abc');
      }

      function test_route_with_query_string() {
         $this->assertRouting('debug?foo%3Dbar=foo+bar', array('controller' => 'debug', 'action' => 'index', 'foo=bar' => 'foo bar'));
         $this->assertRouting('pages/bar/23?foo%3Dbar=foo+bar', array('controller' => 'pages', 'action' => 'bar', 'id' => '23', 'foo=bar' => 'foo bar'));
         $this->assertRouting('browse?foo%3Dbar=foo+bar', array('controller' => 'pages', 'action' => 'browse', 'foo=bar' => 'foo bar'));
      }
   }

?>
