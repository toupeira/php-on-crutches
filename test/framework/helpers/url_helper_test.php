<?# $Id$ ?>
<?

   class UrlHelperTest extends TestCase
   {
      function test_url_for_with_string() {
         $this->assertEqual('foo', url_for('foo'));
      }

      function test_url_for_with_route_string() {
         $this->assertEqual('/foo/bar', url_for(':foo/bar'));
      }

      function test_url_for_with_route_array() {
         $this->assertEqual('/foo/bar',
            url_for(array('controller' => 'foo', 'action' => 'bar')));
      }

      function test_url_for_with_url() {
         $url = "http://www.example.com/foo/bar?baz";
         $this->assertEqual($url, url_for($url));
      }

      function test_url_for_with_absolute_path() {
         $path = "/foo/bar?baz";
         $this->assertEqual($path, url_for($path));
      }

      function test_url_for_with_anchor() {
         $anchor = "#foo";
         $this->assertEqual($anchor, url_for($anchor));
      }

      function test_url_for_with_anchor_suffix() {
         $this->assertEqual('/foo/bar#baz',
            url_for(':foo/bar', array('anchor' => 'baz')));
      }

      function test_url_for_with_full_url() {
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for(':foo/bar', array('full' => true)));
      }

      function test_url_for_with_full_url_from_absolute_path() {
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for('/foo/bar', array('full' => true)));
      }

      function test_url_for_with_full_url_from_relative_path() {
         $_SERVER['REQUEST_URI'] = '/foo/baz';
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for('bar', array('full' => true)));
         $_SERVER['REQUEST_URI'] = '/';
      }

      function test_url_for_with_ssl() {
         $this->assertEqual("https://www.example.com/foo/bar",
            url_for(':foo/bar', array('ssl' => true)));
      }

      function test_url_for_without_url_rewriting() {
         config_set('rewrite_urls', false);
         $this->assertEqual('/index.php?path=foo/bar', url_for(':foo/bar'));
         config_set('rewrite_urls', true);
      }
   }

?>
