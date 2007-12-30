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
    }

    function test_array_find() {
    }

    function test_array_pluck() {
    }
  }

?>
