<?# $Id$ ?>
<?

   class PagesController extends ApplicationController
   {
      function show($path) {
         if ($template = self::find_template("pages/$path") or
             $template = self::find_template("pages/$path/index")) {
            $this->render($template);
         } else {
            raise(MissingTemplate);
         }
      }
   }

?>
