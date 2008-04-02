<?# $Id$ ?>
<?

   class ObjectsTest extends TestCase
   {
      function test_properties() {
         $test = $this->test = new CoreTestObject();
         $this->assertEqual('readonly', $test->readonly);
         $this->assertEqual('readwrite', $test->readwrite);
         $this->assertEqual('shadowed', $test->shadowed);

         $this->assertRaise('$this->test->private');
         $this->assertRaise('$this->test->readonly = "foo"');

         $test->readwrite = 'foo';
         $this->assertEqual('foo', $test->readwrite);

         $test->shadowed = 'foo';
         $this->assertEqual('foo', $test->shadowed);
      }
   }

   class ObjectsTestObject extends Object
   {
      private $private = 'private';
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
         return 'fail';
      }

      function set_shadowed($value) {
         $this->shadowed = 'fail';
      }
   }

?>
