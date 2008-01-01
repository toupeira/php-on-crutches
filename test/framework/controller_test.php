<?# $Id$ ?>
<?

  class ControllerTest extends TestCase
  {
    function setup() {
      $this->controller = new ControllerTestController();
      $this->data = &$this->controller->view->data;
    }

    function assertCalls() {
      $this->assertEqual(func_get_args(), $this->controller->calls);
    }

    function test_construct() {
      $this->assertEqual('controller_test', $this->controller->name);
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
        array('index', 'edit', 'filter'),
        $this->controller->actions);

      $this->assertCalls('init');
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

    function test_is_valid_request() {
    }

    function test_perform() {
    }

    function test_rescue_error_in_public() {
    }

    function test_render() {
    }

    function test_render_text() {
      $text = "<h1>Test</h1>";
      $this->controller->render_text($text);
      $this->assertEqual($text, $this->controller->output);
    }

    function test_redirect_to() {
    }

    function test_send_headers() {
    }

    function test_send_file() {
    }

    function test_add_error() {
    }

    function test_has_errors() {
    }

    function test_set_error_messages() {
    }

    function test_call_filter() {
      $this->controller->call_filter('before');
      $this->assertCalls('init', 'before');
      $this->controller->call_filter('after');
      $this->assertCalls('init', 'before', 'after');
      $this->controller->call_filter('foo');
      $this->assertCalls('init', 'before', 'after');
    }
  }

  class ControllerTestController extends Controller
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

    function index() {
      $this->set('string', 'foo');
      $this->set('array', array('bar'));
      $this->set('integer', 23);
    }

    function edit() {
      
    }

    function filter() {
    }
  }

?>
