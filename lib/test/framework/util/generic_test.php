<?# $Id$ ?>
<?

   class GenericTest extends TestCase
   {
      function test_any() {
         $this->assertTrue(any(true, false));
         $this->assertTrue(any(false, true));
         $this->assertEqual('test', any(false, array(), '', 0, '0', null, 'test'));
      }
   }

?>
