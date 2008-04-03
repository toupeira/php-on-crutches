<?# $Id$ ?>
<?

   class MessageHelperTest extends TestCase
   {
      function setup() {
         $this->controller = Dispatcher::$controller = new MessageHelperTestController();
      }

      function test_messages_without_messages() {
         $this->assertEqual('', messages());
      }
   }

   class MessageHelperTestController extends Controller
   {
   }

?>
