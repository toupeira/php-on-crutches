<?# $Id$ ?>
<?

   class MessageHelperTest extends TestCase
   {
      function test_messages() {
         $this->assertEqual(
            "",
            messages(array()));

         $this->assertEqual(
            '<div class="message error">foo with <code>code</code></div>',
            messages(array('error' => 'foo with [[code]]')));

         $this->assertEqual(
            '<div class="message info">bar</div>',
            messages(array('error' => 'foo', 'info' => 'bar'), array('info')));
      }
   }

?>
