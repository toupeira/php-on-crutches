<?# $Id$ ?>
<?

  class ArrayHelperTest extends TestCase
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
      $this->assertEqual(1, array_delete($this->a, 'a'));
      $this->assertCount(2, $this->a);

      $this->assertEqual(array(2, 3), array_delete($this->a, array('b', 'c')));
      $this->assertCount(0, $this->a);
    }

    function test_array_get() {
      $this->assertEqual(2, array_get($this->a, 'b'));
      $this->assertNull(array_get($this->a, 'd'));
      $this->assertNull(array_get($this->c, 'b'));
    }

    function test_array_find() {
      $this->assertEqual($this->b[2], array_find($this->b, 'name', 'c'));
      $this->assertNull(array_find($this->b, 'name', 'd'));
      $this->assertNull(array_find($this->b, 'foo', 'd'));
      $this->assertNull(array_find($this->c, 'foo', 'd'));
    }

    function test_array_pluck() {
      $this->assertEqual(array('a', 'b', 'c'), array_pluck($this->b, 'name'));
      $this->assertEqual(array(), array_pluck($this->b, 'foo'));
      $this->assertEqual(array(), array_pluck($this->c, 'foo'));
    }
  }

  class ArrayTestObject {
    function __construct($name) {
      $this->name = $name;
    }
  }

?>
