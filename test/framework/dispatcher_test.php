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
         $this->dispatch($path);
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
   }

?>
