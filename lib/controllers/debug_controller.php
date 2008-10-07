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
      protected $_require_ajax = true;

      function is_valid_request($action) {
         if (Dispatcher::$controller instanceof DebugController) {
            return parent::is_valid_request($action);
         } else {
            return true;
         }
      }

      function load($panel=null, $action=null) {
         if ($class = classify($panel.'Panel') and is_subclass_of($class, DebugPanel)) {
            $this->set('panel', $panel = new $class());
            ob_start();
            $output = $panel->{'render_'.$action}();
            if (!$output and !$output = ob_get_clean()) {
               $output = '';
            }
            $this->render_text($output);
         } else {
            throw new NotFound();
         }
      }

      function toolbar($panel=null, $action=null) {
         $panels = array();
         foreach (get_declared_classes() as $class) {
            if (is_subclass_of($class, DebugPanel)) {
               $panels[] = new $class();
            }
         }

         $this->render_text(
            '<div id="debug-toolbar">'
          . '<strong>'.image_tag('framework/icons/debug.png').' Debug</strong>'
          . implode('', array_send($panels, 'render'))
          . '</div>'
         );
      }
   }

   class DebugPanel extends Object
   {
      protected $_name;

      function __construct() {
         $this->_name = underscore(substr(get_class($this), 0, -5));
      }

      function get_panel() {}

      function dump_array($array) {
         if ($array) {
            ob_start();
            print_r($array);
            $lines = explode("\n", ob_get_clean());
            return nl2br(h(implode("\n", array_map(trim, array_slice($lines, 2, -2)))));
         }
      }

      function link_to($text, $action=null) {
         return link_to($text, ":debug/load/{$this->_name}/$action");
      }

      function render() {
         $output = '';
         foreach ((array) $this->panel as $action => $text) {
            if (method_exists($this, render_window)) {
               $window = $this->render_window();
               $output .= "<div id=\"debug-{$this->_name}-window\" style=\"display:none\">$window</div>";
               $text = link_to($text, '#');
            }

            $output .= "<span id=\"debug-{$this->_name}-panel\">$text</span>";
         }

         return $output;
      }
   }

   class RequestPanel extends DebugPanel
   {
      function get_panel() {
         return array(
            'request' => 'Request: '.h(Dispatcher::$path),
         );
      }

      function render_window() {
         $request = array(
            'Path'       => h($_SERVER['REQUEST_URI']),
            'Method'     => $_SERVER['REQUEST_METHOD'],
            'Parameters' => $this->dump_array(Dispatcher::$params),
         );

         if ($_COOKIE) {
            $request['Cookies'] = $this->dump_array($_COOKIE);
         }

         if ($_SESSION) {
            $request['Session'] = $this->dump_array($_SESSION);
         }

         return table_tag($request);
      }
   }

   class LogPanel extends DebugPanel
   {
      function get_panel() {
         return array(
            'log' => 'Log: '.count(Logger::messages()).' entries'
         );
      }

      function render_window() {
         return '<pre>'.colorize(h(implode("\n", (array) Logger::messages()))).'</pre>';
      }
   }

   class SqlPanel extends DebugPanel
   {
      function get_panel() {
         return array(
            'sql' => 'DB: '.Dispatcher::$db_queries.' queries',
         );
      }

      function render_window() {
         $all_queries = array();
         foreach (Dispatcher::$db_queries_sql as $database => $queries) {
            $database = '<strong>'.h($database).':</strong>';
            foreach ($queries as $query) {
               $all_queries[$database][] = '<code>'.h($query).'</code>';
            }
         }

         return list_tag($all_queries);
      }
   }

   class CachePanel extends DebugPanel
   {
   }

?>
