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
         if ($this->is_ajax()) {
         } else {
            $this->set('title', 'PHP Console');
         }
      }

      function sql() {
         if ($this->is_ajax()) {
         } else {
            $this->set('title', 'SQL Console');
         }
      }

      function models($model=null, $action='list', $id=null) {
         $this->set('title', 'Model Browser');

         if ($model and $action) {
            $title = link_to(humanize($model), ":/models/$model");
            if ($action == 'show' or $action == 'edit') {
               $title .= ' <dfn>&#x25b8;</dfn> #'.$id;
            }
            if ($action != 'list' and $action != 'show') {
               $title .= ' <dfn>&#x25b8;</dfn> '.humanize($action);
            }
            $this->set('subtitle', $title);

            if ($action == 'attributes') {
               $this->set('attributes', DB(camelize($model))->attributes);
               $this->render('debug/models/attributes');
            } else {
               $this->model(camelize($model), $action, $id, array(
                  'page_size'   => 20,
                  'path_prefix' => "/models/$model",
                  'template'    => array("debug/models/$action", "scaffold/$action"),
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

            $this->set('databases', $models);
            $this->render('debug/models/index');
         }
      }
   }

?>
