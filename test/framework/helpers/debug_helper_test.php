<?# $Id$ ?>
<?

   class DebugHelperTest extends TestCase
   {
      function test_dump() {
         $this->assertEqual('<pre>1</pre>', dump(1));
         $this->assertEqual('<pre>foo</pre>', dump('foo'));
      }
   }

?>
