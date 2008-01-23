<?# $Id$ ?>
<?

   class ControllerTest extends ControllerTestCase
   {
      function setup() {
         $this->controller = new SampleController();
         $this->data = &$this->controller->view->data;
         $this->views = LIB.'views/sample/';

         mkdir($this->views);
         file_put_contents($this->views.'index.thtml', 'Index Template');
         file_put_contents($this->views.'blank.thtml', 'Blank Template');

         config_set('default_path', 'foo');
         config_set('debug', true);
         config_set('debug_redirects', false);
      }

      function teardown() {
         rm_rf($this->views);
      }

      function assertCalls() {
         $this->assertEqual(func_get_args(), $this->controller->calls);
      }

      function test_find_template() {
      }

      function test_construct() {
         $this->assertEqual('sample', $this->controller->name);
         $this->assertEqual('', $this->controller->output);
         $this->assertIsA($this->controller->view, View);

         foreach (array('params', 'headers', 'cookies', 'files', 'actions', 'errors', 'msg') as $key) {
            $this->assertTrue(is_array($this->controller->$key));
         }

         $this->assertEqual($this->controller->name, $this->data['controller']);
         $this->assertEqual($this->controller->params, $this->data['params']);
         $this->assertEqual($this->controller->cookies, $this->data['cookies']);
         $this->assertEqual($this->controller->msg, $this->data['msg']);

         $this->assertEqual(
            array('index', 'edit', 'filter', 'fail', 'set_headers', 'set_errors'),
            $this->controller->actions);

         $this->assertCalls('init');
      }

      function test_getters() {
         foreach (array('name', 'output') as $attr) {
            $this->assertTrue(is_string($this->controller->$attr));
         }

         foreach (array('params', 'headers', 'cookies', 'files', 'actions', 'errors', 'msg') as $attr) {
            $this->assertTrue(is_array($this->controller->$attr));
         }

         $this->assertIsA($this->controller->view, View);
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
            $this->assertRedirect('foo');
         }

         foreach (array(true, 'edit') as $require) {
            $this->controller->require_post = $require;
            $this->assertFalse($this->controller->is_valid_request('edit'));
            $this->assertRedirect('.');
         }
      }

      function test_is_valid_request_with_ajax_requirement() {
         unset($_SERVER['HTTP_X_REQUESTED_WITH']);

         foreach (array(true, 'index') as $require) {
            $this->controller->require_ajax = $require;
            $this->assertFalse($this->controller->is_valid_request('index'));
            $this->assertRedirect('foo');
         }

         foreach (array(true, 'edit') as $require) {
            $this->controller->require_ajax = $require;
            $this->assertFalse($this->controller->is_valid_request('edit'));
            $this->assertRedirect('.');
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
         $this->assertMatch('/Index Template/', $this->controller->perform('index'));
         $this->assertEqual('index', $this->data['action']);
         $this->assertEqual('application', $this->controller->view->layout);

         $this->assertAssigns(array(
            'string' => 'string', 'array' => 'array', 'integer' => 'integer'
         ));
         $this->assertCalls('init', 'before', 'after');
      }

      function test_perform_with_empty_action() {
         $this->assertMatch('/Blank Template/', $this->controller->perform('blank'));
         $this->assertEqual('blank', $this->data['action']);
         $this->assertEqual('application', $this->controller->view->layout);

         $this->assertNull($this->data['blank']);
         $this->assertCalls('init', 'before', 'after');
      }

      function test_perform_with_invalid_action() {
         foreach (array('init', 'before', 'after', 'before_foo', 'after_foo', 'in-valid', '!@#$%') as $action) {
            $this->controller->perform($action);
            $this->assertEqual(500, $this->controller->headers['Status']);
         }
      }

      function test_perform_with_invalid_request() {
         $this->controller->require_post[] = 'index';
         $this->controller->perform('index');
         $this->assertEqual('foo', $this->controller->headers['Location']);
         $this->assertEqual(302, $this->controller->headers['Status']);
         $this->assertEqual(' ', $this->controller->output);
      }

      function test_perform_with_missing_template() {
         $this->controller->perform('edit');
      }

      function test_perform_with_application_error() {
      }

      function test_perform_with_ajax_request() {
      }

      function test_rescue_error_in_public_404() {
         $this->controller->rescue_error_in_public(new MissingTemplate());
         $this->assertHeader('Status', 404);
      }

      function test_rescue_error_in_public_500() {
         $this->controller->rescue_error_in_public(new ApplicationError());
         $this->assertHeader('Status', 500);
      }

      function test_render() {
      }

      function test_render_text() {
         $text = "<h1>Hi</h1>";
         $this->controller->render_text($text);
         $this->assertEqual($text, $this->controller->output);
      }

      function test_redirect_to() {
         $this->controller->redirect_to('foo');
         $this->assertEqual('foo', $this->controller->headers['Location']);
         $this->assertEqual(302, $this->controller->headers['Status']);
         $this->assertEqual(' ', $this->controller->output);
      }

      function test_redirect_to_with_status() {
         $this->controller->redirect_to('foo', 404);
         $this->assertEqual('foo', $this->controller->headers['Location']);
         $this->assertEqual(404, $this->controller->headers['Status']);
         $this->assertEqual(' ', $this->controller->output);
      }

      function test_redirect_to_with_debug() {
         config_set('debug_redirects', true);

         $this->controller->redirect_to('foo', 404);
         $this->assertEqual(null, $this->controller->headers['Location']);
         $this->assertEqual(null, $this->controller->headers['Status']);
         $this->assertEqual('Redirect to <a href="foo">foo</a>', $this->controller->output);
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

      function test_send_file() {
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

      function test_set_error_messages_without_messages() {
         $foo = new ControllerTestModel();

         $this->controller->set('foo', $foo);
         $this->controller->set_error_messages();
         $this->assertNull($this->controller->msg['error']);
      }

      function test_set_error_messages_with_one_message() {
         $foo = new ControllerTestModel();
         $foo->add_error('foo', "foo is invalid");

         $this->controller->set('foo', $foo);
         $this->controller->set_error_messages();
         $this->assertEqual(
            "foo is invalid",
            $this->controller->msg['error']);
      }

      function test_set_error_messages_with_multiple_messages() {
         $foo = new ControllerTestModel();
         $foo->add_error('foo', "foo is invalid");
         $foo->add_error('bar', "bar is invalid");

         $this->controller->set('foo', $foo);
         $this->controller->set_error_messages();
         $this->assertEqual(
            "<ul><li>foo is invalid</li><li>bar is invalid</li></ul>",
            $this->controller->msg['error']);
      }

      function test_call_filter() {
         $this->controller->call_filter('before');
         $this->assertCalls('init', 'before');
         $this->controller->call_filter('after');
         $this->assertCalls('init', 'before', 'after');
         $this->controller->call_filter('foo');
         $this->assertCalls('init', 'before', 'after');

         $this->assertRaise('$this->controller->call_filter("before_fail")');
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
      function __construct() {
         $this->messages = func_get_args();
      }
   }

?>
