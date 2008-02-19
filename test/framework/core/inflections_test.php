<?# $Id$ ?>
<?

   class TextHelperTest extends TestCase
   {
      function test_h() {
         $this->assertEqual("foo &amp; bar", h("foo & bar"));
      }

      function test_pluralize() {
         $this->assertEqual("3 days", pluralize(3, 'day', 'days'));
         $this->assertEqual("1 week", pluralize(1, 'week', 'weeks'));
         $this->assertEqual("0 months", pluralize(0, 'month', 'months'));
      }

      function test_humanize() {
         $this->assertEqual("Human error", humanize('human_error'));
         $this->assertEqual("Human error", humanize('HumanError'));
      }

      function test_camelize() {
         $this->assertEqual("FooBar", camelize('foo bar'));
         $this->assertEqual("FooBar", camelize('foo_bar'));
         $this->assertEqual("FooBar", camelize('fooBar'));
      }

      function test_underscore() {
         $this->assertEqual("under_score", underscore('UnderScore'));
      }

      function test_truncate() {
         $this->assertEqual("trunc...", truncate("truncate me", 5));
         $this->assertEqual("leave me", truncate("leave me"));
      }

      function test_cycle() {
         $this->assertEqual('foo', cycle('foo', 'bar'));
         $this->assertEqual('bar', cycle('foo', 'bar'));
         $this->assertEqual('foo', cycle('foo', 'bar'));
         $this->assertEqual('bar', cycle('foo', 'bar'));
      }

      function test_br2nl() {
         $this->assertEqual("foo\nbar", br2nl("foo<br />bar"));
      }
   }

?>
