<?# $Id$ ?>
<?

   class ObjectsTest extends TestCase
   {
      function setup() {
         $this->object = new ObjectsTestObject();
      }

      function test_properties() {
         $this->assertEqual('readonly', $this->object->readonly);
         $this->assertEqual('readwrite', $this->object->readwrite);
         $this->assertEqual('shadowed', $this->object->shadowed);

         $this->assertRaise('$this->object->private', UndefinedMethod);
         $this->assertRaise('$this->object->readonly = "foo"', UndefinedMethod);

         $this->object->readwrite = 'foo';
         $this->assertEqual('foo', $this->object->readwrite);

         $this->object->shadowed = 'foo';
         $this->assertEqual('foo', $this->object->shadowed);
      }

      function test_call_if_defined() {
         $this->assertNull($this->object->call_if_defined('foo'));
         $this->assertEqual('bar', $this->object->call_if_defined('bar'));
      }

      function test_call_filter() {
         $this->object->call_filter('good_filter');
         $this->assertEqual('good_filter', $this->object->readonly);

         $this->assertRaise('$this->object->call_filter("bad_filter")');
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

      function bar() {
         return 'bar';
      }

      function good_filter() {
         $this->readonly = 'good_filter';
      }

      function bad_filter() {
         return false;
      }
   }

?>
