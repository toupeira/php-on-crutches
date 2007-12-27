<?# $Id$ ?>
<?

  class PagesController extends ApplicationController
  {
    function show($path) {
      if ($template = $this->find_template($path) or
          $template = $this->find_template("$path/index")) {
        $this->render($template);
      } else {
        $this->render_text("sali, no $path");
        #$this->error(404);
      }
    }
  }

?>
