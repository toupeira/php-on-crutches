<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

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
         $this->assertTrue($raised, "Expected code to raise $class");
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

      function assertRecognizes($expected_values, $path) {
         $values = Router::recognize($path);
         $message = "Route '$path' wasn't recognized as "
                  . array_to_str($expected_values) . ", got "
                  . array_to_str($values);
         return $this->assertEqual($expected_values, $values, str_replace('%', '%%', $message));
      }

      function assertGenerates($expected_path, $values) {
         $path = Router::generate($values);
         $message = "Expected " . array_to_str($values) . " to "
                  . "generate '$expected_path', got '$path'";
         return $this->assertEqual($expected_path, $path, str_replace('%', '%%', $message));
      }

      function assertRouting($path, $values) {
         $this->assertRecognizes($values, $path);
         $this->assertGenerates($path, $values);
      }
   }

   class ModelTestCase extends TestCase
   {
   }

   class ControllerTestCase extends TestCase
   {
      function setup() {
         $class = substr(get_class($this), 0, -4);
         $this->controller = Dispatcher::$controller = new $class();
         $this->data = &$this->controller->view->data;
         $this->setup_controller();
      }

      function setup_controller() {}

      function request($action, $get=null, $post=null) {
         $path = "{$this->controller->name}/$action";
         $_SERVER['REQUEST_URI'] = "/$path";
         $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
         $_SERVER['REQUEST_METHOD'] = (is_array($post) ? 'POST' : 'GET');
         $_GET = (array) $get;
         $_POST = (array) $post;

         list($this->controller, $this->action, $this->args) =
            Dispatcher::recognize($path);
         $this->controller->perform($this->action, $this->args);
         $this->data = &$this->controller->view->data;
      }

      function get($action, $args=null) {
         return $this->request($action, (array) $args);
      }

      function post($action, $args=null) {
         return $this->request($action, null, (array) $args);
      }

      function ajax($method, $action, $args=null) {
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
         $this->$method($action, $args);
         unset($_SERVER['HTTP_X_REQUESTED_WITH']);
      }

      function assertResponse($response) {
         $status = $this->controller->headers['Status'];

         switch ($response) {
            case 200:
            case 'success':
               $this->assertInArray($status, array(200, null),
                  "Expected a successful response, got '$status'");
               $this->assertFalse(is_null($this->controller->output));
               break;
            case 301:
            case 302:
            case 'redirect':
               $this->assertInArray($status, array(301, 302),
                  "Expected a redirect, got '$status'");
               $this->assertOutput(' ');
               break;
            case 404:
            case 'notfound':
               $this->assertEqual(404, $status,
                  "Expected a 404, got '$status'");
               break;
            case 500:
            case 'error':
               $this->assertEqual(500, $status,
                  "Expected an error response, got '$status'");
               break;
            default:
         }
      }

      function assertHeader($header, $value) {
         $real_value = $this->controller->headers[$header];
         $this->assertTrue(
            is_null($value) ? is_null($real_value)
                            : strstr($real_value, "$value"),
            "Expected header '$header' to contain '$value', got '$real_value'");
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

      function assertLayout($layout) {
         $this->assertEqual($layout, $this->controller->view->layout,
           "Expected layout '$layout', got {$this->controller->view->layout}");
      }

      function assertOutput($text) {
         $this->assertEqual($text, $this->controller->output);
      }

      function assertOutputMatch($pattern) {
         $this->assertMatch($pattern, $this->controller->output);
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

?>
