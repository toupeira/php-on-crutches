<?# $Id$ ?>
<?

   class PhpBreakageTest extends TestCase
   {
      function test_truth() {
         $this->assertTrue(1);
         $this->assertTrue('1');
         $this->assertTrue(' ');
         $this->assertTrue(array(0));
         $this->assertTrue(array(''));
         $this->assertTrue(array(' '));

         $this->assertFalse(0);
         $this->assertFalse('0');
         $this->assertFalse('');
         $this->assertFalse(null);
         $this->assertFalse(array());
      }

      function test_equality() {
         $this->assertTrue(1 == true);
         $this->assertTrue(0 == false);
         $this->assertTrue(1 == '1');
         $this->assertTrue(0 == '0');
         $this->assertTrue(null == 0);
         $this->assertTrue(null == '');
         $this->assertTrue(null == array());

         $this->assertFalse(1 === true);
         $this->assertFalse(0 === false);
         $this->assertFalse(1 === '1');
         $this->assertFalse(0 === '0');
         $this->assertFalse(null === 0);
         $this->assertFalse(null === '');
         $this->assertFalse(null === array());
      }

      function test_comparison() {
         $this->assertTrue(array() > 0);
         $this->assertTrue(0 < array());

         $this->assertFalse(0 > '');
         $this->assertFalse('' > 0);

         $this->assertTrue('aa' > 'a');
         $this->assertTrue('a' < 'aa');
      }

      function test_emptiness() {
         foreach (array('', 0, '0', array(), null) as $value) {
            $this->assertTrue(empty($value));
         }

         foreach (array(' ', 1, '1', array(''), array(1)) as $value) {
            $this->assertFalse(empty($value));
         }
      }

      function test_strict_binding() {
         $this->assertEqual('PhpBreakageParent', PhpBreakageParent::get_class());
         $this->assertEqual('PhpBreakageParent', PhpBreakageChild::get_class());
         $parent = new PhpBreakageParent();
         $child = new PhpBreakageChild();
         $this->assertEqual('parent', $parent->get_property());
         $this->assertEqual('parent', $child->get_property());
      }
   }

   class PhpBreakageParent
   {
      static $class = 'parent';

      static function get_class() {
         return get_class();
      }

      function get_property() {
         return self::$class;
      }
   }

   class PhpBreakageChild extends PhpBreakageParent
   {
      static $class = 'child';
   }

?>
