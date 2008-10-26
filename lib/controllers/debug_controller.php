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

      function database($model=null, $action='index', $id=null) {
         $this->view->layout = 'database';

         if ($model and $action) {
            $title = link_to(humanize($model), ":/database/$model");
            if ($action != 'index') {
               $title .= ' &#x25b8; '.humanize($action);
            }
            $this->set('title', $title);

            if ($action == 'attributes') {
               $this->set('attributes', DB(camelize($model))->attributes);
               $this->render('debug/attributes');
            } else {
               $this->model(camelize($model), $action, $id, array(
                  'page_size'   => 20,
                  'redirect_to' => ":/database/$model",
               ));
            }
         } else {
            # Load all model files
            foreach (glob(MODELS.'*.php') as $file) {
               require $file;
            }

            # Find all ActiveRecord models
            $models = array();
            foreach (get_declared_classes() as $class) {
               if (is_subclass_of($class, ActiveRecord)) {
                  $models[] = $class;
               }
            }

            $this->set('title', 'Models');
            $this->set('models', $models);
            $this->render('debug/database');
         }
      }
   }

?>
