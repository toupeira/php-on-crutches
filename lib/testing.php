<?
/*
  PHP on Crutches - Copyright (c) 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License.

  $Id$
*/

  require 'simpletest/unit_tester.php';
  require 'simpletest/reporter.php';

  @include TEST.'test_helper.php';

  function run_tests($path, $message=null, $reporter=null) {
    $group = new GroupTest($message);
    $name = basename($path);

    if (is_file($path)) {
      $message = "Testing $path: ";
      $group->addTestFile($path);
    } else {
      $message = "Testing $name: ";

      $dir = TEST.$name;
      foreach (explode("\n", `find "$dir" -type f -name '*.php'`) as $file) {
        if (is_file($file)) {
          $group->addTestFile($file);
        }
      }
    }

    print $message.$group->getSize().' tests';
    if ($group->getSize() > 0) {
      $reporter = any($reporter, new Reporter());
      $group->run($reporter);
      print "\n";
      return $reporter->getStatus();
    } else {
      print "\n\n";
      return true;
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
                 . "] should be in ["
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

    function assertHasError($object, $key) {
      $dumper = &new SimpleDumper();
      $message = "[" . $dumper->describeValue($object)
               . "] should have an error on ["
               . $dumper->describeValue($key) . "]";
      return $this->assertInArray(
        $key, $object->errors, $message);
    }
  }

  class ModelTestCase extends TestCase
  {
  }

  class ControllerTestCase extends TestCase
  {
    function __construct() {
      parent::__construct();
      $class = substr(get_class($this), 0, -4);
      $this->controller = Dispatcher::$controller = new $class();
    }

    function request($action, $get=null, $post=null) {
      $_SERVER['REQUEST_METHOD'] = (is_array($post) ? 'POST' : 'GET');
      $_GET = (array) $get;
      $_POST = (array) $post;

      list($this->controller, $this->action, $this->args) =
        Dispatcher::recognize("{$this->controller->name}/$action");
      $this->controller->perform($this->action, $this->args);
      $this->data = &$this->controller->view->data;
    }

    function get($action, $args=null) {
      return $this->request($action, (array) $args);
    }

    function post($action, $args=null) {
      return $this->request($action, null, (array) $args);
    }

    function assertResponse($response) {
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

    function assertHeader($header, $value) {
      $this->assertTrue(
        strstr($this->controller->headers[$header], $value),
        "Expected header '$header' to contain '$value'");
    }

    function assertTemplate($template) {
      $this->assertResponse('success');

      $parts = explode('/', "$template.thtml");
      $view_template = $this->controller->view->template;
      $view_parts = explode('/', $view_template);
      $this->assertEqual(
        $parts,
        array_slice($view_parts, -count($parts)),
        "Expected template '$template', got '$view_template'");
    }

    function assertAssigns($assigns) {
      foreach ($assigns as $key => $type) {
        $this->assertTrue(
          array_key_exists($key, $this->data),
          "Expected assigned variable '$key'");
        $this->assertEqual(
          gettype($this->data[$key]), $type,
          "Expected assigned variable '$key' to be of type '$type', got '".gettype($this->data[$key])."'");
      }
    }

    function assertRedirect($path) {
      $this->assertResponse('redirect');

      $url = url_for($path);
      $real_url = $this->controller->headers['Location'];
      $this->assertEqual($url, $real_url,
        "Expected redirect to '$url', got '$real_url'");
    }

    function assertMessage($key) {
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