<?# $Id$ ?>
<?

  require 'simpletest/unit_tester.php';
  require 'simpletest/reporter.php';

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

    function assertInArray($member, $array, $message=null) {
      if (!$message) {
        $dumper = &new SimpleDumper();
        $message = "[" . $dumper->describeValue($member)
                 . "] should be in [" .
                 . $dumper->describeValue($array) . "]";
      }
      return $this->assertTrue(
        in_array($member, $array), $message);
    }

    function assertCount($count, $value, $message=null) {
      if (!$message) {
        $dumper = &new SimpleDumper();
        $message = "[" . $dumper->describeValue($value)
                 . "] should have a count of ["
                 . $dumper->describeValue($count) . "]";
      }
      return $this->assertEqual(
        $count, count($value), $message);
    }

    function assertRaise($code, $class=ApplicationError) {
      $raised = false;
      try {
        eval("$code;");
      } catch (ApplicationError $e) {
        if ($e instanceof $class) {
          $raised = true;
        }
      }
      $this->assertTrue($raised, "expected code to raise $class");
      return $e;
    }

    function assertFileContents($text, $file, $message="%s") {
      return $this->assertEqual($text, trim(file_get_contents($file)), $message);
    }

    function assertFileMatch($pattern, $file, $message="%s") {
      return $this->assertWantedPattern($pattern, file_get_contents($file), $message);
    }
  }

  class ModelTestCase extends TestCase
  {
    function assertHasError($object, $key) {
      $dumper = &new SimpleDumper();
      $message = "[" . $dumper->describeValue($object)
               . "] should have an error on ["
               . $dumper->describeValue($key) . "]";
      return $this->assertInArray(
        $key, $object->errors, $message);
    }
  }

  class ControllerTestCase extends TestCase
  {
    function setup() {
      $class = substr(get_class($this), 0, -4);
      $this->controller = Dispatcher::$controller = new $class();
    }

    function request($action, $get=null, $post=null) {
      $GLOBALS['_SERVER']['REQUEST_METHOD'] = ($post ? 'POST' : 'GET');
      $GLOBALS['_GET'] = (array) $get;
      $GLOBALS['_POST'] = (array) $post;

      list($this->controller, $this->action, $this->args) =
        Dispatcher::recognize("{$this->controller->name}/$action");
      $this->controller->perform($this->action, $this->args);
      $this->data = &$this->controller->view->data;
    }

    function get($action, $args=null) {
      return $this->request($action, $args);
    }

    function post($action, $args=null) {
      return $this->request($action, null, $args);
    }

    function assertTemplate($template, $message="%s") {
      $parts = explode('/', "$template.thtml");
      $view_template = $this->controller->view->template;
      $view_parts = explode('/', $view_template);
      $this->assertEqual(
        $parts,
        array_slice($view_parts, -count($parts)),
        "Expected template '$template', got '$view_template'");
    }

    function assertAssigns($assigns, $message="%s") {
      foreach ($assigns as $key => $type) {
        $this->assertTrue(
          array_key_exists($key, $this->data),
          "Expected assigned variable '$key'");
        $this->assertEqual(
          gettype($this->data[$key]), $type,
          "Expected assigned variable '$key' to be of type '$type', got '".gettype($this->data[$key])."'");
      }
    }

    function assertResponse($response, $message="%s") {
      $status = $this->controller->headers['Status'];

      switch ($response) {
        case 'redirect':
          $this->assertInArray($status, array(301, 302),
            "Expected a redirect, got '$status'");
          break;
        case 'success':
          $this->assertInArray($status, array(200, null),
            "Expected a successful response, got '$status'");
          break;
        case 'error':
          $this->assertInArray($status, array(500),
            "Expected an error response, got '$status'");
          break;
        default:
      }
    }

    function assertRedirect($path, $message="%s") {
      $url = url_for($path);
      $real_url = $this->controller->headers['Location'];
      $this->assertEqual($url, $real_url,
        "Expected redirect to '$url', got '$real_url'");
    }

    function assertMessage($key, $message="%s") {
      $this->assertFalse(empty($this->controller->msg[$key]),
        "Expected message on key '$key'");
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
