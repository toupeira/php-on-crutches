<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class DebugController extends Controller
   {
      protected $_require_trusted = true;
      protected $_layout = 'debug';

      function php() {
         if ($this->is_post() or $this->is_ajax('post')) {
            $input = $this->params['input'];
            $code = syntax_highlight($input);

            ob_start();
            try {
               eval("$input;");
               $output = ob_get_clean();
            } catch (Exception $e) {
               ob_end_clean();
               $output = $e->getMessage();
            }

            $this->render_text("<code>&gt;&gt;&gt; </code>$code<br />".syntax_highlight($output)."<br />");
         }
      }

      function sql() {
         if ($this->is_ajax()) {
         }
      }

      function models($model=null, $action='index', $id=null) {
         $this->set('title', 'Model Browser');
         $this->set('icon', 'database');

         if ($model and $action) {
            $title = image_tag('framework/icons/database.png') . ' '
                   . link_to(humanize($model), ":/models/$model");

            if ($action == 'show' or $action == 'edit') {
               $title .= ' <dfn>&#x25b8;</dfn> #'.$id;
            }
            if ($action != 'index' and $action != 'show') {
               $title .= ' <dfn>&#x25b8;</dfn> '.humanize($action);
            }
            $this->set('subtitle', $title);

            if ($action == 'attributes') {
               $this->set('model', $model);
               $this->set('attributes', DB(camelize($model))->attributes);
               $this->render('debug/models/attributes');
            } else {
               $template = ($action == 'index' ? 'list' : $action);
               $this->scaffold($action, $id, array(
                  'model'     => $model,
                  'paginate'  => 25,
                  'order'     => false,
                  'prefix'    => "/models/$model",
                  'template'  => array("debug/models/$template", "scaffold/$template"),
               ));
            }
         } else {
            # Load all model files
            foreach (glob(MODELS.'*.php') as $file) {
               require_once $file;
            }

            # Find all ActiveRecord models
            $models = array();
            foreach (get_declared_classes() as $model) {
               $class = new ReflectionClass($model);
               if ($class->isSubclassOf(ActiveRecord) and $class->isInstantiable()) {
                  $models[DB($model)->connection->display_name][$model] = DB($model)->count;
               }
            }

            $this->set('databases', $models);
            $this->render('debug/models/index');
         }
      }
   }

?>
