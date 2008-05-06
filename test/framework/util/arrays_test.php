<?# $Id$ ?>
<?

   class ArraysTest extends TestCase
   {
      function setup() {
         $this->a = array(
            'a' => 1,
            'b' => 2,
            'c' => 3,
         );

         $this->b = array(
            new ArraysTestObject('a'),
            new ArraysTestObject('b'),
            new ArraysTestObject('c'),
         );
      }

      function test_array_delete() {
         $this->assertEqual(1, array_delete($this->a, 'a'));
         $this->assertCount(2, $this->a);

         $this->assertEqual(array(2, 3), array_delete($this->a, array('b', 'c')));
         $this->assertCount(0, $this->a);
      }

      function test_array_get() {
         $this->assertEqual(array('b' => 2),
            array_get($this->a, 'b'));
         $this->assertEqual(array('b' => 2),
            array_get($this->a, array('b')));
         $this->assertEqual(array('a' => 1, 'c' => 3),
            array_get($this->a, 'a', 'c'));
         $this->assertEqual(array('a' => 1, 'c' => 3),
            array_get($this->a, array('a', 'c')));

         $this->assertEqual(array(), array_get($this->a, null));
         $this->assertEqual(array(), array_get($this->a, array()));
         $this->assertEqual(array('d' => null), array_get($this->a, 'd'));
      }

      function test_array_find() {
         $this->assertEqual($this->b[2], array_find($this->b, 'name', 'c'));
         $this->assertNull(array_find($this->b, 'name', 'd'));
         $this->assertNull(array_find($this->b, 'foo', 'd'));
      }

      function test_array_pluck() {
         $this->assertEqual(array('a', 'b', 'c'), array_pluck($this->b, 'name'));
         $this->assertEqual(array(), array_pluck($this->b, 'foo'));
      }
   }

   class ArraysTestObject {
      function __construct($name) {
         $this->name = $name;
      }
   }

?>
