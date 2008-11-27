<?# $Id$ ?>
<?

   function url_for_with_prefix() {
      config_set('prefix', '/sub/');
      $args = func_get_args();
      $result = call_user_func_array('url_for', $args);
      config_set('prefix', '/');

      return $result;
   }

   class UrlHelperTest extends TestCase
   {
      function test_url_for_with_string() {
         $this->assertEqual('/foo', url_for('foo'));
         $this->assertEqual('/sub/foo', url_for_with_prefix('foo'));
      }

      function test_url_for_with_route_string() {
         $this->assertEqual('/foo/bar', url_for(':foo/bar'));
         $this->assertEqual('/sub/foo/bar', url_for_with_prefix(':foo/bar'));
      }

      function test_url_for_with_empty_string() {
         $this->assertEqual('/', url_for(''));
         $this->assertEqual('/sub/', url_for_with_prefix(''));

         $this->assertEqual('http://www.example.com/', url_for('', array('full' => true)));
         $this->assertEqual('http://www.example.com/sub/', url_for_with_prefix('', array('full' => true)));
      }

      function test_url_for_with_route_array() {
         $this->assertEqual('/foo/bar',
            url_for(array('controller' => 'foo', 'action' => 'bar')));
         $this->assertEqual('/sub/foo/bar',
            url_for_with_prefix(array('controller' => 'foo', 'action' => 'bar')));
      }

      function test_url_for_with_url() {
         $url = "http://www.example.com/foo/bar?baz";
         $this->assertEqual($url, url_for($url));
         $this->assertEqual($url, url_for_with_prefix($url));
      }

      function test_url_for_with_absolute_path() {
         $path = "/foo/bar?baz";
         $this->assertEqual($path, url_for($path));
         $this->assertEqual($path, url_for_with_prefix($path));
      }

      function test_url_for_with_relative_path() {
         $path = "index.html";
         $this->assertEqual($path, url_for("./$path"));
         $this->assertEqual($path, url_for_with_prefix("./$path"));
      }

      function test_url_for_with_anchor() {
         $anchor = "#foo";
         $this->assertEqual($anchor, url_for($anchor));
         $this->assertEqual($anchor, url_for_with_prefix($anchor));
      }

      function test_url_for_with_anchor_suffix() {
         $this->assertEqual('/foo/bar#baz',
            url_for(':foo/bar', array('anchor' => 'baz')));
         $this->assertEqual('/sub/foo/bar#baz',
            url_for_with_prefix(':foo/bar', array('anchor' => 'baz')));
      }

      function test_url_for_with_full_url() {
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for(':foo/bar', array('full' => true)));
         $this->assertEqual("http://www.example.com/sub/foo/bar",
            url_for_with_prefix(':foo/bar', array('full' => true)));
      }

      function test_url_for_with_full_url_from_absolute_path() {
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for('/foo/bar', array('full' => true)));
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for_with_prefix('/foo/bar', array('full' => true)));
      }

      function test_url_for_with_full_url_from_relative_path() {
         $this->assertEqual("http://www.example.com/bar",
            url_for('./bar', array('full' => true)));
         $this->assertEqual("http://www.example.com/sub/bar",
            url_for_with_prefix('bar', array('full' => true)));

         $_SERVER['REQUEST_URI'] = '/foo/baz';
         $this->assertEqual("http://www.example.com/foo/bar",
            url_for('./bar', array('full' => true)));

         $_SERVER['REQUEST_URI'] = '/sub/foo/baz';
         $this->assertEqual("http://www.example.com/sub/foo/bar",
            url_for_with_prefix('./bar', array('full' => true)));

         $_SERVER['REQUEST_URI'] = '/';
      }

      function test_url_for_with_ssl() {
         $this->assertEqual("https://www.example.com/foo/bar",
            url_for(':foo/bar', array('ssl' => true)));
         $this->assertEqual("https://www.example.com/sub/foo/bar",
            url_for_with_prefix(':foo/bar', array('ssl' => true)));
      }

      function test_url_for_without_url_rewriting() {
         config_set('rewrite_urls', false);
         $this->assertEqual('/index.php?path=foo/bar', url_for(':foo/bar'));
         $this->assertEqual('/sub/index.php?path=foo/bar', url_for_with_prefix(':foo/bar'));
         config_set('rewrite_urls', true);
      }
   }

?>
