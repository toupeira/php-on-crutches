<?# $Id$ ?>
<?

  class View extends Object
  {
    public $data;

    private $layout;
    private $template;

    function __construct() {
    }

    function get_layout() {
      return $this->layout;
    }

    function set_layout($layout) {
      $this->layout = $layout;
    }

    function get_template() {
      return $this->template;
    }

    function set_template($template) {
      $this->template = $template;
    }

    function render() {
      extract($this->data, EXTR_SKIP);

      ob_start();
      require $this->template;
      $content_for_layout = ob_get_clean();

      $layout = VIEWS.'layouts/'.basename($this->layout).'.thtml';
      if (is_file($layout)) {
        ob_start();
        require $layout;
        $output = ob_get_clean();
      } else {
        $output = $content_for_layout;
      }

      log_debug("Rendered template {$this->template}");

      return $output;
    }

    private function render_partial($partial, $locals=null) {
      $partial = '_'.basename($partial).'.thtml';
      if (!is_file($template = dirname($this->template).'/'.$partial) and
         !is_file($template = VIEWS.$partial)) {
        raise("Partial not found: $partial");
      }

      extract($this->data, EXTR_SKIP);
      if (is_array($locals)) {
        extract($locals, EXTR_SKIP);
      }

      ob_start();
      require $template;
      $output = ob_get_clean();

      log_debug("Rendered partial $template");

      return $output;
    }
  }

?>
