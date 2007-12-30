<?# $Id$ ?>
<?

  class ConfigTest extends TestCase
  {
    function setup() {
      $this->real_config = $GLOBALS['_CONFIG'];
      $GLOBALS['_CONFIG'] = array(
        'foo' => 'bar',
      );
    }

    function teardown() {
      $GLOBALS['_CONFIG'] = $this->real_config;
    }

    function test_config() {
      $this->assertEqual('bar', config('foo'));
      config_set('foo', 'test');
      $this->assertEqual('test', config('foo'));
    }
  }

?>
