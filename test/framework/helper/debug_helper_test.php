<?# $Id$ ?>
<?

   class DebugHelperTest extends TestCase
   {
      function test_dump_error() {
         $output = dump_error(new MissingTemplate('foo'));

         $this->assertMatch(
            "#^<h1>Missing Template</h1>\n<p>foo</p>\n<pre>.*#",
            $output);
      }
   }

?>
