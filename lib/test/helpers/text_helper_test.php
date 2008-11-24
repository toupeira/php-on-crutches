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

      function test_truncate() {
         $this->assertEqual("trunc...", truncate("truncate me", 5));
         $this->assertEqual("leave me", truncate("leave me"));
      }

      function test_simple_format() {
         $this->assertEqual("&lt;h1&gt;foo&lt;/h1&gt;<br />\nbar", simple_format("<h1>foo</h1>\nbar"));
      }

      function test_auto_link() {
         $url = "http://foo/?id=1&bar=2";
         $h_url = h($url);

         $this->assertEqual("<a href=\"$h_url\">$h_url</a>", auto_link($url));
         $this->assertEqual("(<a href=\"$h_url\">$h_url</a>)", auto_link("($url)"));
         $this->assertEqual("<a href=\"$h_url\">$h_url</a>,", auto_link("$url,"));

         $this->assertEqual("<a href=\"http://www.foo.com\">www.foo.com</a>", auto_link('www.foo.com'));
      }

      function test_br2nl() {
         $this->assertEqual("foo\nbar", br2nl("foo<br />bar"));
      }

      function test_cycle() {
         $this->assertEqual('foo', cycle('foo', 'bar'));
         $this->assertEqual('bar', cycle('foo', 'bar'));
         $this->assertEqual('foo', cycle('foo', 'bar'));
         $this->assertEqual('bar', cycle('foo', 'bar'));
      }
   }

?>
