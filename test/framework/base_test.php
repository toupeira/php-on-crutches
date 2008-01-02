<?# $Id$ ?>
<?

  class BaseTest extends TestCase
  {
    function test_object() {
      $test = $this->test = new BaseTestObject();
      $this->assertEqual('readonly', $test->readonly);
      $this->assertEqual('readwrite', $test->readwrite);
      $this->assertEqual('shadowed', $test->shadowed);

      $this->assertRaise('$this->test->private');
      $this->assertRaise('$this->test->readonly = "foo"');

      $test->readwrite = 'foo';
      $this->assertEqual('foo', $test->readwrite);

      $test->shadowed = 'foo';
      $this->assertEqual('foo', $test->shadowed);
    }

    function test_any() {
      $this->assertTrue(any(true, false));
      $this->assertTrue(any(false, true));
      $this->assertEqual('test', any(false, array(), '', 0, '0', null, 'test'));
    }

    function test_run() {
      $this->assertTrue(run('true'));
      $this->assertFalse(run('false'));
    }

    function test_mktemp() {
      $file = mktemp();
      $this->assertTrue(is_file($file));
      $this->assertMatch('#^/tmp/'.config('application').'\.\w{6}#', $file);

      $dir = mktemp(true);
      $this->assertTrue(is_dir($dir));
      $this->assertMatch('#^/tmp/'.config('application').'\.\w{6}#', $file);
    }

    function test_rm_f() {
      $this->assertNull(rm_f('/tmp/invalid/file'));
      $file = mktemp();
      $this->assertTrue(rm_f($file));
      $this->assertFalse(is_file($file));
    }

    function test_exceptions() {
      $this->assertTrue(class_exists(ApplicationError));
      $this->assertTrue(class_exists(MissingTemplate));
    }

    function test_raise_with_message() {
      $e = $this->assertRaise('raise("foo")');
      $this->assertEqual('foo', $e->getMessage());
    }

    function test_raise_with_exception() {
      $e = $this->assertRaise('raise(new MissingTemplate("bar"))');
      $this->assertIsA($e, MissingTemplate);
      $this->assertEqual('bar', $e->getMessage());
    }

    function test_raise_with_class() {
      $e = $this->assertRaise('raise(MissingTemplate)');
      $this->assertIsA($e, MissingTemplate);
      $this->assertEqual('', $e->getMessage());
    }

    function test_dump_error() {
      $output = dump_error(new MissingTemplate('foo'));

      $this->assertMatch(
        "#^<h1>Missing template</h1>\n<p>foo</p>\n<pre>.*#",
        $output);
    }
  }

  class BaseTestObject extends Object
  {
    private $private = 'private';
    private $readonly = 'readonly';
    private $readwrite = 'readwrite';
    public $shadowed = 'shadowed';

    function get_readonly() {
      return $this->readonly;
    }

    function get_readwrite() {
      return $this->readwrite;
    }

    function set_readwrite($value) {
      $this->readwrite = $value;
    }

    function get_shadowed() {
      return 'fail';
    }

    function set_shadowed($value) {
      $this->shadowed = 'fail';
    }
  }

?>
