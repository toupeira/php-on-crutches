<?# $Id$ ?>
<?

   class DispatcherTestController extends ApplicationController {};

   class DispatcherTest extends TestCase
   {
      function test_run() {
      }

      function test_recognize() {
         list($controller, $action, $args) = Dispatcher::recognize('dispatcher_test/show/foo');
         $this->assertIsA($controller, DispatcherTestController);
         $this->assertEqual('show', $action);
         $this->assertEqual(array('foo'), $args);

         $this->assertEqual($controller, Dispatcher::$controller);
         $this->assertEqual($action, Dispatcher::$action);
      }

      function test_recognize_without_action() {
         list($controller, $action, $args) = Dispatcher::recognize('dispatcher_test');
         $this->assertIsA($controller, DispatcherTestController);
         $this->assertEqual('index', $action);
      }

      function test_recognize_invalid() {
         list($controller, $action, $args) = Dispatcher::recognize('does/not/exist');
         $this->assertIsA($controller, PagesController);
         $this->assertEqual('show', $action);
         $this->assertEqual('does/not/exist', $args);
      }

      function test_log_header() {
      }

      function test_log_request() {
      }
   }

?>
