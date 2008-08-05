<?# $Id$ ?>
<?

   class InflectionsTest extends TestCase
   {
      function test_humanize() {
         $this->assertEqual("Human error", humanize('human_error'));
         $this->assertEqual("Human error", humanize('HumanError'));
      }

      function test_titleize() {
         $this->assertEqual("Human Error", titleize('human_error'));
         $this->assertEqual("Human Error", titleize('HumanError'));
      }

      function test_camelize() {
         $this->assertEqual("FooBar", camelize('foo bar'));
         $this->assertEqual("FooBar", camelize('foo_bar'));
         $this->assertEqual("FooBar", camelize('fooBar'));
      }

      function test_underscore() {
         $this->assertEqual("under_score", underscore('UnderScore'));
         $this->assertEqual("under_score", underscore('Under Score'));
         $this->assertEqual("under_score", underscore('UNDER  score'));
      }
   }

?>
