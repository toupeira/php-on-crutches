<?# $Id$ ?>
<?

  $GLOBALS['_CONFIG'] = array(
    'foo' => 'bar',
  );

  class ConfigTest extends TestCase
  {
    function test_config() {
      $this->assertEqual('bar', config('foo'));
      config_set('foo', 'test');
      $this->assertEqual('test', config('foo'));
    }
  }

?>
