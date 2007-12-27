<?# $Id$ ?>
<?

  require_once 'simpletest/unit_tester.php';
  require_once 'simpletest/reporter.php';

  function run_tests($dirs, $message=null, $reporter=null) {
    $group = new GroupTest($message);
    foreach ((array) $dirs as $dir) {
      foreach (glob(TEST.basename($dir)."/*.php") as $file) {
        $group->addTestFile($file);
      }
    }

    $reporter = any($reporter, new TextReporter());
    print "\n";
    $group->run($reporter);
    print "\n";
  }

  class TestCase extends UnitTestCase
  {
    function assertInArray($member, $array, $message = "%s") {
      $dumper = &new SimpleDumper();
      $message = sprintf(
        $message,
        "[" . $dumper->describeValue($member) .
        "] should be in [" .
        $dumper->describeValue($array) . "]");
      return $this->assertTrue(
        in_array($member, $array), $message);
    }

    function assertCount($count, $value, $message = "%s") {
      $dumper = &new SimpleDumper();
      $message = sprintf(
        $message,
        "[" . $dumper->describeValue($value) .
        "] should have a count of [" .
        $dumper->describeValue($count) . "]");
      return $this->assertEqual(
        $count, count($value), $message);
    }
  }

  class ModelTest extends TestCase
  {
    function assertError($object, $key, $message="%s") {
      $dumper = &new SimpleDumper();
      $message = sprintf(
        $message,
        "[" . $dumper->describeValue($object) .
        "] should have an error on [" .
        $dumper->describeValue($key) . "]");
      return $this->assertInArray(
        $key, $object->errors, $message);
    }
  }

  class ControllerTest extends TestCase
  {
  }

?>
