<?# $Id$ ?>
<?

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
   }

?>
