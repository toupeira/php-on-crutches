<?# $Id$ ?>
<?

   class ControllerTest extends ControllerTestCase
   {
      function setup() {
         $this->controller = new SampleController();
         $this->data = &$this->controller->view->data;
         $this->views = LIB.'views/sample/';
         $this->send_file_output = null;
         register_shutdown_function(rm_rf, $this->views);

         mkdir($this->views);
         file_put_contents($this->views.'index.thtml', 'Index Template');
         file_put_contents($this->views.'blank.thtml', 'Blank Template');
      }

      function teardown() {
         rm_rf($this->views);
      }

      function send_file($file, $options=null) {
         ob_start();
         $status = $this->controller->send_file($file, $options);
         $this->send_file_output = ob_get_clean();
         return $status;
      }

      function assertCalls() {
         $this->assertEqual(func_get_args(), $this->controller->calls);
      }

      function test_construct() {
         $this->assertEqual('sample', $this->controller->name);
         $this->assertEqual('', $this->controller->output);

         foreach (array('params', 'session', 'headers', 'cookies', 'files', 'actions', 'errors', 'msg') as $key) {
            $this->assertTrue(is_array($this->controller->$key));
         }

         $this->assertIsA($this->controller->view, View);

         $this->assertEqual($this->controller->name, $this->data['controller']);
         $this->assertEqual($this->controller->params, $this->data['params']);
         $this->assertEqual($this->controller->cookies, $this->data['cookies']);
         $this->assertEqual($this->controller->msg, $this->data['msg']);

         $this->assertEqual(
            array('index', 'edit', 'filter', 'fail', 'set_headers', 'set_errors'),
            $this->controller->actions);

         $this->assertCalls('init');
      }

      function test_construct_with_saved_messages() {
         $msg = array('info' => "Foobar!!!");
         $_SESSION['msg'] = $msg;
         $this->controller = new SampleController();
         $this->assertEqual($msg, $this->controller->msg);
         $this->assertNull($_SESSION['msg']);
      }

      function test_getters() {
         foreach (array('name', 'layout', 'output') as $attr) {
            $this->assertTrue(is_string($this->controller->$attr));
         }

         $this->assertIsA($this->controller->view, View);

         foreach (array('params', 'session', 'headers', 'cookies', 'files', 'actions', 'errors', 'msg') as $attr) {
            $this->assertTrue(is_array($this->controller->$attr));
         }
      }

      function test_get() {
         $this->assertEqual($this->data['controller'], $this->controller->get('controller'));
      }

      function test_set() {
         $this->controller->set('foo', 'bar');
         $this->assertEqual('bar', $this->data['foo']);

         $data = array(1, 2, 3);
         $this->controller->set('data', &$data);
         $this->assertEqual(
            array(1, 2, 3),
            $this->data['data']);

         $data[] = 4;
         $this->assertEqual(
            array(1, 2, 3, 4),
            $this->data['data']);
      }

      function test_is_post() {
         $this->assertFalse($this->controller->is_post());
         $_SERVER['REQUEST_METHOD'] = 'foo';
         $this->assertFalse($this->controller->is_post());
         $_SERVER['REQUEST_METHOD'] = 'GET';
         $this->assertFalse($this->controller->is_post());
         $_SERVER['REQUEST_METHOD'] = 'POST';
         $this->assertTrue($this->controller->is_post());
      }

      function test_is_ajax() {
         $this->assertFalse($this->controller->is_ajax());
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'foo';
         $this->assertFalse($this->controller->is_ajax());
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
         $this->assertFalse($this->controller->is_ajax());
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
         $this->assertTrue($this->controller->is_ajax());
      }

      function test_is_ssl() {
         $this->assertFalse($this->controller->is_ssl());
         $_SERVER['HTTPS'] = '';
         $this->assertFalse($this->controller->is_ssl());
         $_SERVER['HTTPS'] = 'off';
         $this->assertFalse($this->controller->is_ssl());
         $_SERVER['HTTPS'] = 'on';
         $this->assertTrue($this->controller->is_ssl());
         $_SERVER['HTTPS'] = 'yes';
         $this->assertTrue($this->controller->is_ssl());
      }

      function test_is_valid_request_without_requirements() {
         $_SERVER['REQUEST_METHOD'] = 'GET';

         $this->assertTrue($this->controller->is_valid_request('index'));
      }

      function test_is_valid_request_with_post_requirement() {
         $_SERVER['REQUEST_METHOD'] = 'GET';

         foreach (array(true, 'index') as $require) {
            $this->controller->require_post = $require;
            $this->assertFalse($this->controller->is_valid_request('index'));
            $this->assertRedirect('/');
         }

         foreach (array(true, 'edit') as $require) {
            $this->controller->require_post = $require;
            $this->assertFalse($this->controller->is_valid_request('edit'));
            $this->assertRedirect(':');
         }
      }

      function test_is_valid_request_with_ajax_requirement() {
         unset($_SERVER['HTTP_X_REQUESTED_WITH']);

         foreach (array(true, 'index') as $require) {
            $this->controller->require_ajax = $require;
            $this->assertFalse($this->controller->is_valid_request('index'));
            $this->assertRedirect('/');
         }

         foreach (array(true, 'edit') as $require) {
            $this->controller->require_ajax = $require;
            $this->assertFalse($this->controller->is_valid_request('edit'));
            $this->assertRedirect(':');
         }
      }

      function test_is_valid_request_with_ssl_requirement() {
         unset($_SERVER['HTTPS']);
         $_SERVER['SERVER_NAME'] = 'example.com';
         $_SERVER['REQUEST_URI'] = '/foo?bar';

         foreach (array(true, 'index') as $require) {
            $this->controller->require_ssl = $require;
            $this->assertFalse($this->controller->is_valid_request('index'));
            $this->assertRedirect("https://example.com/foo?bar");
         }
      }

      function test_perform_with_valid_action() {
         $this->controller->perform('index');
         $this->assertOutputMatch('/Index Template/');
         $this->assertEqual('index', $this->data['action']);
         $this->assertLayout('application');

         $this->assertAssigns(array(
            'string' => 'string', 'array' => 'array', 'integer' => 'integer'
         ));
         $this->assertCalls('init', 'before', 'after');
      }

      function test_perform_with_empty_action() {
         $this->controller->perform('blank');
         $this->assertOutputMatch('/Blank Template/');
         $this->assertEqual('blank', $this->data['action']);
         $this->assertLayout('application');

         $this->assertNull($this->data['blank']);
         $this->assertCalls('init', 'before', 'after');
      }

      function test_perform_with_invalid_action() {
         foreach (array('init', 'before', 'after', 'before_foo', 'after_foo', 'in-valid', '_invalid', '!@#$%') as $action) {
            $this->assertRaise("\$this->controller->perform('$action')", RoutingError);
         }
      }

      function test_perform_with_invalid_request() {
         $this->controller->require_post[] = 'index';
         $this->controller->perform('index');
         $this->assertRedirect('/');
      }

      function test_perform_with_missing_template() {
         $this->assertRaise("\$this->controller->perform('edit')", MissingTemplate);
      }

      function test_perform_with_ajax_request() {
         $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';

         $this->controller->perform('index');
         $this->assertOutput('Index Template');
         $this->assertLayout('');

         unset($_SERVER['HTTP_X_REQUESTED_WITH']);
      }

      function test_render_with_action() {
         $this->assertTrue($this->controller->render('index'));
         $this->assertOutput('Index Template');
      }

      function test_render_with_action_and_missing_template() {
         $this->assertRaise('$this->controller->render("edit")', MissingTemplate);
         $this->assertOutput(null);
      }

      function test_render_with_layout() {
         $layout = md5(time());
         file_put_contents(VIEWS."layouts/$layout.thtml", '<start><?= $content_for_layout ?><end>');

         $this->assertTrue($this->controller->render('index', $layout));
         $this->assertOutput('<start>Index Template<end>');

         rm_f(VIEWS."layouts/$layout.thtml");
      }

      function test_render_with_template() {
         $this->assertTrue($this->controller->render($this->views.'blank.thtml'));
         $this->assertOutput('Blank Template');
      }

      function test_render_with_missing_template() {
         $this->assertRaise('$this->controller->render("/invalid/template")', MissingTemplate);
         $this->assertOutput(null);
      }

      function test_render_twice() {
         $this->assertTrue($this->controller->render('index'));
         $this->assertRaise('$this->controller->render("index")');
      }

      function test_render_text() {
         $text = "<h1>Hi</h1>";
         $this->controller->render_text($text);
         $this->assertEqual($text, $this->controller->output);
      }

      function test_redirect_to() {
         $this->controller->redirect_to('/foo');
         $this->assertHeader('Location', 'http://www.example.com/foo');
         $this->assertHeader('Status', 302);
         $this->assertEqual(' ', $this->controller->output);
      }

      function test_redirect_to_with_status() {
         $this->controller->redirect_to('/foo', 404);
         $this->assertHeader('Location', 'http://www.example.com/foo');
         $this->assertHeader('Status', 404);
         $this->assertEqual(' ', $this->controller->output);
      }

      function test_redirect_to_with_debug() {
         config_set('debug_redirects', true);

         $this->controller->redirect_to('/foo', 404);
         $this->assertHeader('Location', null);
         $this->assertHeader('Status', null);
         $this->assertOutput('Redirect to <a href="http://www.example.com/foo">http://www.example.com/foo</a>', $this->controller->output);

         config_set('debug_redirects', false);
      }

      function test_send_headers() {
         $headers = array(
            'foo' => 'FOO',
            'bar' => 'BAR',
            'num' => 666,
         );

         $this->controller->headers = $headers;
         $this->assertTrue($this->controller->send_headers());
      }

      function test_send_file_with_valid_file() {
         $this->assertTrue($this->send_file(__FILE__));
         $this->assertHeader('Content-Disposition', 'attachment');
         $this->assertHeader('Content-Type', mime_content_type(__FILE__));
         $this->assertHeader('Content-Length', filesize(__FILE__));
         $this->assertOutput('');
         $this->assertEqual(file_get_contents(__FILE__), $this->send_file_output);
      }

      function test_send_file_with_missing_file() {
         $this->assertRaise('$this->send_file("/invalid/file")');
         $this->assertOutput(null);
         $this->assertEqual(array(), $this->controller->headers);
         $this->assertNull($this->send_file_output);
      }

      function test_send_file_with_invalid_file() {
         $file = '/etc/shadow';
         $this->assertRaise("\$this->send_file('$file')");
         $this->assertHeader('Content-Disposition', 'attachment');
         $this->assertHeader('Content-Type', null);
         $this->assertHeader('Content-Length', filesize($file));
         $this->assertOutput('');
         $this->assertNull($this->send_file_output);
      }

      function test_send_file_with_name() {
         $this->assertTrue($this->send_file(__FILE__, array('name' => 'foo"bar')));
         $this->assertHeader('Content-Disposition', 'attachment; filename="foo\"bar"');
      }

      function test_send_file_with_size() {
         $this->assertTrue($this->send_file(__FILE__, array('size' => '666')));
         $this->assertHeader('Content-Length', 666);
      }

      function test_send_file_with_type() {
         $this->assertTrue($this->send_file(__FILE__, array('type' => 'foo')));
         $this->assertHeader('Content-Type', 'foo');
      }

      function test_send_file_inline() {
         $this->assertTrue($this->send_file(__FILE__, array('inline' => true)));
         $this->assertHeader('Content-Disposition', null);
      }

      function test_send_file_with_command() {
         $this->assertTrue($this->send_file('!echo -n "foo   bar"'));
         $this->assertOutput('');
         $this->assertEqual('foo   bar', $this->send_file_output);
      }

      function test_add_error() {
         $this->controller->add_error('foo', "foo is invalid");
         $this->assertEqual(array('foo'), $this->controller->errors);
         $this->assertEqual("foo is invalid", $this->controller->msg['error']);
         $this->controller->add_error('foo', "foo is invalid");
         $this->assertEqual(array('foo'), $this->controller->errors);
         $this->assertEqual("foo is invalid", $this->controller->msg['error']);

         $this->controller->add_error('bar', "bar is invalid");
         $this->assertEqual(array('foo', 'bar'), $this->controller->errors);
         $this->assertEqual("bar is invalid", $this->controller->msg['error']);
      }

      function test_has_errors() {
         $this->controller->errors = array('foo');
         $this->assertTrue($this->controller->has_errors('foo'));
         $this->assertFalse($this->controller->has_errors('bar'));
         $this->assertTrue($this->controller->has_errors('foo[]'));
         $this->assertFalse($this->controller->has_errors('bar[]'));
      }

      function test_set_error_messages() {
         $foo = new ControllerTestModel();
         $foo->add_error('foo', "foo is invalid");
         $foo->add_error('bar', "bar is invalid");

         $this->controller->set('foo', $foo);
         $this->controller->set_error_messages();
         $this->assertEqual(
            array('foo is invalid', 'bar is invalid'),
            $this->controller->msg['error']);
      }

      function test_set_error_messages_without_messages() {
         $foo = new ControllerTestModel();

         $this->controller->set('foo', $foo);
         $this->controller->set_error_messages();
         $this->assertNull($this->controller->msg['error']);
      }
   }

   class SampleController extends Controller
   {
      public $require_post;
      public $require_ajax;
      public $require_ssl;

      public $calls;

      protected function init() {
         $this->calls[] = 'init';
      }

      protected function before() {
         $this->calls[] = 'before';
      }

      protected function after() {
         $this->calls[] = 'after';
      }

      protected function before_filter() {
         $this->calls[] = 'before_filter';
      }

      protected function after_filter() {
         $this->calls[] = 'after_filter';
      }

      protected function before_fail() {
         return false;
      }

      function index() {
         $this->set('string', 'foo');
         $this->set('array', array('bar'));
         $this->set('integer', 23);
      }

      private function blank() {
         $this->set('blank', true);
      }

      function edit() {
      }

      function filter() {
      }

      function fail() {
         raise("I failed");
      }

      function set_headers($headers) {
         $this->headers = $headers;
      }

      function set_errors($errors) {
         $this->errors = $errors;
      }
   }

   class ControllerTestModel extends Model
   {
   }

?>
