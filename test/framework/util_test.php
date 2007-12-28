<?# $Id$ ?>
<?

   class TestObject extends Object
   {
      private $readonly = 'readonly';
      private $readwrite = 'readwrite';
      public $shadowed = 'shadowed';

      function get_readonly() {
         return $this->readonly;
      }

      function get_readwrite() {
         return $this->readwrite;
      }

      function set_readwrite($value) {
         $this->readwrite = $value;
      }

      function get_shadowed() {
         return 'failed';
      }
   }

   class UtilTest extends TestCase
   {
      function test_object() {
      }
   }

?>
