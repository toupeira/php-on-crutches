<?# $Id$ ?>
<?

   class ArrayTest extends TestCase
   {
      function setup() {
         $this->a = array(
            'a' => 1,
            'b' => 2,
            'c' => 3,
         );

         $this->b = array(
            new ArrayTestObject('a'),
            new ArrayTestObject('b'),
            new ArrayTestObject('c'),
         );
      }

      function test_array_delete() {
         $this->assertEqual(1, hash::delete($this->a, 'a'));
         $this->assertCount(2, $this->a);

         $this->assertEqual(array(2, 3), hash::delete($this->a, array('b', 'c')));
         $this->assertCount(0, $this->a);
      }

      function test_array_get() {
         $this->assertEqual(2,
            hash::get($this->a, 'b'));
         $this->assertEqual(array('b' => 2),
            hash::get($this->a, array('b')));
         $this->assertEqual(array('a' => 1, 'c' => 3),
            hash::get($this->a, 'a', 'c'));
         $this->assertEqual(array('a' => 1, 'c' => 3),
            hash::get($this->a, array('a', 'c')));

         $this->assertNull(hash::get($this->a, 'd'));
         $this->assertNull(hash::get($this->c, 'b'));
      }

      function test_array_find() {
         $this->assertEqual($this->b[2], set::find($this->b, 'name', 'c'));
         $this->assertNull(set::find($this->b, 'name', 'd'));
         $this->assertNull(set::find($this->b, 'foo', 'd'));
         $this->assertNull(set::find($this->c, 'foo', 'd'));
      }

      function test_array_pluck() {
         $this->assertEqual(array('a', 'b', 'c'), set::pluck($this->b, 'name'));
         $this->assertEqual(array(), set::pluck($this->b, 'foo'));
         $this->assertEqual(array(), set::pluck($this->c, 'foo'));
      }
   }

   class ArrayTestObject {
      function __construct($name) {
         $this->name = $name;
      }
   }

?>
