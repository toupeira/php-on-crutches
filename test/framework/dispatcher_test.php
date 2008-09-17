<?# $Id$ ?>
<?

   class DispatcherTestController extends Controller {};

   class DispatcherTest extends TestCase
   {
      function teardown() {
         Dispatcher::$prefix = $_SERVER['REQUEST_URI'] = '/';
      }

      function dispatch($path) {
         ob_start();
         $this->controller = Dispatcher::run($path);
         $this->output = ob_get_clean();
      }

      function assertPrefix($prefix, $path, $url) {
         $_SERVER['REQUEST_URI'] = $url;
         try {
            $this->dispatch($path);
         } catch (ApplicationError $e) {
         }

         $this->assertEqual($prefix, Dispatcher::$prefix,
            "Expected prefix '$prefix', got '".Dispatcher::$prefix."'");

      }

      function test_prefix() {
         $this->assertPrefix('/', '/', '/');
         $this->assertPrefix('/', '/', '/index.php');
         $this->assertPrefix('/', 'pages', '/pages');
         $this->assertPrefix('/', 'pages', '/pages/');
         $this->assertPrefix('/', 'pages', '/index.php?path=///pages');
         $this->assertPrefix('/', 'pages/foo', '/pages/foo/');
         $this->assertPrefix('/', 'pages/foo', '/index.php?path=pages/foo');
         $this->assertPrefix('/', '/pages/foo', '///pages/foo');
         $this->assertPrefix('/', '/pages/foo', '/index.php?path=/pages/foo');
         $this->assertPrefix('/', '/pages/foo', '///index.php?path=/pages/foo/');

         $this->assertPrefix('/foo/', '/', '/foo');
         $this->assertPrefix('/foo/', '/', '/foo/');
         $this->assertPrefix('/foo/', '/', '/foo/index.php');
         $this->assertPrefix('/foo/', 'pages', '/foo/pages');
         $this->assertPrefix('/foo/', 'pages', '/foo/index.php?path=pages');
         $this->assertPrefix('/foo/', 'pages/bar', '/foo///pages/bar');
         $this->assertPrefix('/foo/', 'pages/bar', '/foo/index.php?path=pages/bar');
         $this->assertPrefix('/foo/', '/pages/bar', '/foo/pages/bar');
         $this->assertPrefix('/foo/', '/pages/bar', '/foo/index.php?path=///pages/bar/');
         $this->assertPrefix('///foo/', '/pages/bar', '///foo/index.php?path=/pages/bar');
      }

      function test_run() {
         $path = $_GET['path'] = 'errors/show/404?foo=bar';
         $start_time = microtime(true);
         $this->dispatch($path);

         $this->assertIsA($this->controller, ErrorsController);
         $this->assertEqual($this->controller, Dispatcher::$controller);

         $this->assertEqual("/$path", Dispatcher::$path);
         $this->assertEqual(null, $_GET['path']);
         $this->assertEqual('/', Dispatcher::$prefix);
         $this->assertEqual(
            array(
               'action'     => 'show',
               'controller' => 'errors',
               'foo'        => 'bar',
               'id'         => '404',
            ),
            Dispatcher::$params
         );

         $this->assertTrue(!blank($this->output));
         $this->assertEqual($this->output, Dispatcher::$controller->output);

         $this->assertTrue(Dispatcher::$start_time - $start_time < 1);
         $this->assertEqual(0, Dispatcher::$render_time);
         $this->assertEqual(0, Dispatcher::$db_queries);
      }
   }

?>
