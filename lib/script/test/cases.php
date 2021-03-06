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
      public $_load_fixtures = true;

      # Call custom invoker
      function &createInvoker() {
         return new SimpleErrorTrappingInvoker(
            new CustomInvoker($this)
         );
      }

      function assert($value, $message="%s") {
         return $this->assertTrue($value, $message);
      }

      function assertSame($first, $second, $message="%s") {
         return $this->assertIdentical($first, $second, $message);
      }

      function assertMatch($pattern, $subject, $message="%s") {
         return $this->assertWantedPattern($pattern, $subject, $message);
      }

      function assertInArray($member, array $array, $message=null) {
         if (!$message) {
            $dumper = &new SimpleDumper();
            $message = "[" . $dumper->describeValue($member)
                     . "] should be in ["
                     . $dumper->describeValue($array) . "]";
         }

         return $this->assertTrue(
            in_array($member, $array), $message);
      }

      function assertKey($key, array $array, $message=null) {
         if (!$message) {
            $dumper = &new SimpleDumper();
            $message = "[" . $dumper->describeValue($array)
                     . "] should have a key ["
                     . $dumper->describeValue($key) . "]";
         }

         return $this->assertTrue(
            isset($array[$key]), $message);
      }

      function assertCount($count, $value, $message=null) {
         if (!$message) {
            $dumper = &new SimpleDumper();
            $message = "[" . $dumper->describeValue($value)
                     . "] should have a count of ["
                     . $dumper->describeValue($count) . "]"
                     . ", got [".$dumper->describeValue(count($value)) . "]";
         }

         return $this->assertSame(
            round($count), round(count($value)), $message);
      }

      function assertRaise($code, $class=ApplicationError) {
         $raised = false;
         try {
            eval("$code;");
         } catch (Exception $e) {
            if ($e instanceof $class) {
               $raised = true;
            }
         }

         $this->assertTrue($raised, "Expected code to raise $class");
         return $e;
      }

      function assertFileContents($text, $file, $message="%s") {
         return $this->assertSame($text, trim(file_get_contents($file)), $message);
      }

      function assertFileMatch($pattern, $file, $message="%s") {
         return $this->assertWantedPattern($pattern, file_get_contents($file), $message);
      }

      function assertHasError(Model $object, $key) {
         $dumper = &new SimpleDumper();
         $message = "[" . $dumper->describeValue($object)
                  . "] should have an error on ["
                  . $dumper->describeValue($key) . "]";

         return $this->assertKey(
            $key, $object->errors, $message);
      }

      function assertRecognizes(array $expected_values, $path) {
         $values = Router::recognize($path);
         sort($values);
         sort($expected_values);

         $message = "Route '$path' wasn't recognized as "
                  . array_to_str($expected_values) . ", got "
                  . array_to_str($values);

         return $this->assertSame($expected_values, $values, str_replace('%', '%%', $message));
      }

      function assertGenerates($expected_path, array $values) {
         $path = Router::generate($values);
         $message = "Expected " . array_to_str($values) . " to "
                  . "generate '$expected_path', got '$path'";

         return $this->assertSame($expected_path, $path, str_replace('%', '%%', $message));
      }

      function assertRouting($path, array $values) {
         return $this->assertRecognizes($values, $path) and $this->assertGenerates($path, $values);
      }

      function assertMailSent() {
         $all_options = func_get_args();
         $count_options = count($all_options);
         $count_mails = count($GLOBALS['_SENT_MAILS']);

         if (!$this->assertSame($count_options, $count_mails,
               "Expected $count_options sent mails, got $count_mails")) {
            return false;
         }

         $i = 0;
         foreach ($all_options as $options) {
            foreach ($options as $key => $value) {
               if ($key == 'template') {
                  $value = VIEWS."$value.thtml";
               } elseif ($key == 'layout' and $value) {
                  $value = VIEWS."layouts/$value.thtml";
               }

               $mail_value = $GLOBALS['_SENT_MAILS'][$i][$key];
               if (is_array($mail_value) and !is_array($value)) {
                  if (!$this->assertInArray($value, $mail_value)) {
                     return false;
                  }
               } else {
                  if (!$this->assertSame($value, $mail_value)) {
                     return false;
                  }
               }
            }

            $i++;
         }

         return true;
      }
   }

   class ModelTestCase extends TestCase
   {
   }

   class ControllerTestCase extends TestCase
   {
      protected $controller;
      protected $action;
      protected $stdout;

      protected $session;
      protected $cookies;

      function setup($class=null) {
         if (!$class) {
            $class = substr(get_class($this), 0, -4);
         }

         $this->controller = Dispatcher::$controller = new $class();

         $this->session = &$_SESSION;
         $this->cookies = &$_COOKIE;

         $this->setup_controller();
      }

      function setup_controller() {}

      function assigns($key=null) {
         if ($key) {
            return $this->controller->view->data[$key];
         } else {
            return $this->controller->view->data;
         }
      }

      function request($action, array $post=null) {
         $path = "{$this->controller->name}/$action";
         $_POST = (array) $post;
         fake_request($path, is_array($post) ? 'POST' : 'GET');

         ob_start();
         $this->controller = Dispatcher::run($path);
         $this->stdout = ob_get_clean();
         $this->action = Dispatcher::$params['action'];
      }

      function get($action) {
         return $this->request($action);
      }

      function post($action, array $args=null) {
         return $this->request($action, (array) $args);
      }

      function ajax($method, $action, array $args=null) {
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
         $this->$method($action, $args);
         unset($_SERVER['HTTP_X_REQUESTED_WITH']);
      }

      function assertResponse($response) {
         $status = $this->controller->headers['Status'];

         switch ($response) {
            case 200:
            case 'success':
               return $this->assertInArray($status, array(200, null), "Expected a successful response, got '$status'") and
                      $this->assertFalse(is_null($this->controller->output));
               break;
            case 301:
            case 302:
            case 'redirect':
               return $this->assertInArray($status, array(301, 302), "Expected a redirect, got '$status'") and
                      $this->assertOutput(' ');
               break;
            case 404:
            case 'notfound':
               return $this->assertSame(404, $status, "Expected a 404, got '$status'");
               break;
            case 500:
            case 'error':
               return $this->assertSame(500, $status, "Expected an error response, got '$status'");
               break;
            default:
         }
      }

      function assertHeader($header, $value) {
         $real_value = $this->controller->headers[$header];
         return $this->assertTrue(
            is_null($value) ? is_null($real_value)
                            : strstr($real_value, "$value"),
            "Expected header '$header' to contain '$value', got '$real_value'");
      }

      function assertTemplate($template) {
         $this->assertResponse('success');

         $view_template = (string) $this->controller->view->template;

         if ($template) {
            $parts = explode('/', "$template.thtml");
            $view_parts = explode('/', $view_template);
            return $this->assertSame(
               $parts,
               array_slice($view_parts, -count($parts)),
               "Expected template '$template', got '$view_template'");
         } else {
            return $this->assertSame('', $view_template,
               "Expected template '$template', got '$view_template'");
         }
      }

      function assertAssigns(array $assigns) {
         foreach ($assigns as $key => $type) {
            if (!$this->assertTrue(array_key_exists($key, $this->assigns()), "Expected assigned variable '$key'")) {
               return false;
            }

            if (class_exists($type)) {
               $real_type = get_class($this->assigns($key));
            } else {
               $real_type = gettype($this->assigns($key));
            }

            if (!$this->assertSame($type, $real_type,
                "Expected assigned variable '$key' to be of type '$type', got '$real_type'")) {
               return false;
            }
         }
      }

      function assertLayout($layout) {
         $real_layout = (string) substr(basename($this->controller->view->layout), 0, -6);
         return $this->assertSame($layout, $real_layout,
           "Expected layout '$layout', got '$real_layout'");
      }

      function assertOutput($text) {
         return $this->assertSame($text, $this->controller->output);
      }

      function assertOutputMatch($pattern) {
         return $this->assertMatch($pattern, $this->controller->output);
      }

      function assertRedirect($path) {
         if (!$this->assertResponse('redirect')) {
            return false;
         }

         $url = url_for($path, array('full' => true));
         $real_url = $this->controller->headers['Location'];

         return $this->assertSame($url, $real_url,
            "Expected redirect to '$url', got '$real_url'");
      }

      function assertMessage($key) {
         return $this->assertFalse(empty($this->controller->msg[$key]),
            "Expected message on key '$key'");
      }
   }

?>
