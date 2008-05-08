<?# $Id$ ?>
<?

   class MessageHelperTest extends TestCase
   {
      function setup() {
         $this->controller = Dispatcher::$controller = new MessageHelperTestController();
         $this->msg = &$this->controller->msg;
         $this->msg = array();
      }

      function test_messages_without_messages() {
         $this->assertEqual('', messages());
      }
   }

   class MessageHelperTestController extends Controller
   {
   }

?>
