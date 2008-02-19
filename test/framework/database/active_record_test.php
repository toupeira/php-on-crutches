<?# $Id$ ?>
<?

   require_once LIB.'database/base.php';

   class ActiveRecordTest extends TestCase
   {
      function setup() {
         $this->model = new SampleActiveRecord();
      }

      function test_construct_exceptions() {
         foreach (array(EmptyActiveRecord, EmptyTableActiveRecord) as $class) {
            $this->assertRaise("new $class()");
         }
      }
   }

   class SampleActiveRecord extends ActiveRecord
   {
      protected $table = 'sample';
      protected $load_attributes = false;
   }

   class EmptyActiveRecord extends ActiveRecord {
      protected $database = '';
      protected $table = 'foo';
   }

   class EmptyTableActiveRecord extends ActiveRecord {
      protected $table = '';
   }

?>
