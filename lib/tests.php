<?# $Id$ ?>
<?

  require_once 'simpletest/unit_tester.php';
  require_once 'simpletest/reporter.php';

  function run_tests($dir, $message=null, $reporter=null) {
    $group = new GroupTest($message);
    foreach (glob(TEST.basename($dir)."/*.php") as $file) {
      $group->addTestFile($file);
    }

    if ($group->getSize() > 0) {
      print "Running ".basename($dir)." tests";
      $reporter = any($reporter, new Reporter());
      $group->run($reporter);
    }
  }

  class Reporter extends TextReporter
  {
    function paintPass($message) {
      print ".";
      parent::paintPass($message);
    }

    function paintFail($message) {
      print "F\n";
      parent::paintFail($message);
    }

    function paintError($message) {
      print "E\n";
      parent::paintError($message);
    }

    function paintGroupEnd($test_name) {
      parent::paintGroupEnd($test_name);
      print "\n";
    }

    function paintCaseStart($test_name) {
      print "$test_name:\n";
      parent::paintCaseStart($test_name);
    }
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

  class HelperTest extends TestCase
  {
  }

  class ViewTest extends TestCase
  {
  }

  class IntegrationTest extends TestCase
  {
  }

?>
