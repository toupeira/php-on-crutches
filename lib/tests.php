<?# $Id$ ?>
<?

  require_once 'simpletest/unit_tester.php';
  require_once 'simpletest/reporter.php';

  function run_tests($path, $message=null, $reporter=null) {
    $group = new GroupTest($message);
    $name = basename($path);

    if (is_file($path)) {
      $message = "Running $path...";
      $group->addTestFile($path);
    } else {
      $message = "Running $name tests...";

      $dir = TEST.$name;
      foreach (explode("\n", `find "$dir" -type f -name '*.php'`) as $file) {
        if (is_file($file)) {
          $group->addTestFile($file);
        }
      }
    }

    if ($group->getSize() > 0) {
      print $message;
      $reporter = any($reporter, new Reporter());
      $group->run($reporter);
      print "\n";
    }
  }

  class TestCase extends UnitTestCase
  {
    function assertMatch($pattern, $subject, $message="%s") {
      return $this->assertWantedPattern($pattern, $subject, $message);
    }

    function assertInArray($member, $array, $message="%s") {
      $dumper = &new SimpleDumper();
      $message = sprintf(
        $message,
        "[" . $dumper->describeValue($member) .
        "] should be in [" .
        $dumper->describeValue($array) . "]");
      return $this->assertTrue(
        in_array($member, $array), $message);
    }

    function assertCount($count, $value, $message="%s") {
      $dumper = &new SimpleDumper();
      $message = sprintf(
        $message,
        "[" . $dumper->describeValue($value) .
        "] should have a count of [" .
        $dumper->describeValue($count) . "]");
      return $this->assertEqual(
        $count, count($value), $message);
    }

    function assertHasError($object, $key, $message="%s") {
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
  }

?>
