<?# $Id$ ?>
<?

   require_once LIB.'database/base.php';

   class DatabaseModelTest extends TestCase
   {
      function setup() {
         $this->model = new SampleDatabaseModel();
      }

      function test_construct_exceptions() {
         foreach (array(EmptyDatabaseModel, EmptyTableModel, EmptyPrimaryKeyModel) as $class) {
            $this->assertRaise("new $class()");
         }
      }
   }

   class SampleDatabaseModel extends DatabaseModel
   {
      protected $table = 'sample';
      protected $load_attributes = false;
   }

   class EmptyDatabaseModel extends DatabaseModel {
      protected $database = '';
      protected $table = 'foo';
   }

   class EmptyTableModel extends DatabaseModel {
      protected $table = '';
   }

   class EmptyPrimaryKeyModel extends DatabaseModel {
      protected $table = 'foo';
      protected $primary_key = '';
   }

?>
