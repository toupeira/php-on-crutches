<?# $Id$ ?>
<?

   class DispatcherTestController extends Controller {};

   class DispatcherTest extends TestCase
   {
      function dispatch($path) {
         ob_start();
         $this->controller = Dispatcher::run($path);
         $this->output = ob_get_clean();
      }

      function test_run() {
         $path = $_GET['path'] = 'errors/show/404?foo=bar';
         $start_time = microtime(true);
         $this->dispatch($path);

         $this->assertIsA($this->controller, ErrorsController);
         $this->assertEqual($this->controller, Dispatcher::$controller);

         $this->assertEqual("/$path", Dispatcher::$path);
         $this->assertEqual(null, $_GET['path']);
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
         $this->assertTrue(Dispatcher::$db_queries >= 0);
      }
   }

?>
