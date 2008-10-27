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
               $title .= ' <dfn>&#x25b8;</dfn> '.humanize($action);
            }
            $this->set('title', $title);

            if ($action == 'attributes') {
               $this->set('attributes', DB(camelize($model))->attributes);
               $this->render('debug/database/attributes');
            } else {
               $this->model(camelize($model), $action, $id, array(
                  'page_size'   => 20,
                  'redirect_to' => ":/database/$model",
                  'template'    => array("debug/database/$action", "scaffold/$action"),
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
                  $models[DB($class)->connection->name][$class] = DB($class)->count;
               }
            }

            $this->set('title', 'Models');
            $this->set('databases', $models);
            $this->render('debug/database/models');
         }
      }
   }

?>
