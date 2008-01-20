<?# $Id$ ?>
<?

   require_once LIB.'database/base.php';

   class DatabaseModelTest extends TestCase
   {
      function setup() {
         $this->model = new SampleDatabaseModel();
      }

      function test_construct() {
      }
   }

   class SampleDatabaseModel extends DatabaseModel
   {
      protected $table = 'sample';
      protected $load_attributes = false;
   }

?>
