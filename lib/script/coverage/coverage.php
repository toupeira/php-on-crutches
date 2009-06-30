<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class CoverageReport extends Object
   {
      protected $_files;
      protected $_target;
      protected $_include;
      protected $_exclude;
      protected $_reports;
      protected $_view_path;

      protected $_total_size = 0;
      protected $_total_code = 0;
      protected $_covered_size = 0;
      protected $_covered_code = 0;
      protected $_comment = false;
      protected $_last = true;

      function __construct(array $coverage, $target=null, $include=null, $exclude=null) {
         $this->_files = $coverage;
         $this->_target = $target;

         if (is_array($include)) {
            $this->_include = $include;
         } else {
            $this->_include = APP;
         }

         $this->_exclude = (array) $exclude;

         $this->_view_path = LIB.'script/coverage/views/';
      }

      function generate($force=false) {
         print "Generating code coverage report in [1m{$this->_target}[0m...\n";

         if (file_exists($this->_target) and !$force) {
            print "Error: Target path {$this->_target} already exists.\n";
            return false;
         } else { 
            rm_rf($this->_target);
         }

         if (!mkdir($this->_target)) {
            print "Error: Could not create target path {$this->_target}.\n";
            return false;
         }

         if (!copy($this->_view_path.'coverage.css', "{$this->_target}/coverage.css")) {
            print "Error: Could not copy stylesheet.\n";
            return false;
         }

         ksort($this->_files);
         $filtered = array();

         foreach ($this->_files as $file => $lines) {
            if (is_file($file)) {
               $skip = false;
               foreach ((array) $this->_exclude as $base_path) {
                  if (strpos($file, $base_path) === 0) {
                     $skip = true;
                     break;
                  }
               }
               if ($skip) continue;

               foreach ((array) $this->_include as $base_path) {
                  if (strpos($file, $base_path) === 0) {
                     $filtered[$file] = $lines;
                     break;
                  }
               }
            }
         }

         $this->_files = $filtered;
         print pluralize(count($this->_files), 'file')." found:\n";

         foreach ($this->_files as $path => $coverage) {
            $this->render_file($path, $coverage);
         }

         $this->render_index();
         print "Done.\n\n";

         return true;
      }

      protected function render_file($path, array $coverage) {
         $name = substr($path, strlen(ROOT));
         $file = underscore($name).'.html';

         print "  creating $file\n";
         if (!$lines = file($path)) {
            throw new ApplicationError("Could not open file $path");
         }

         $size = count($lines);
         $code = 0;
         $covered_size = 0;
         $covered_code = 0;

         $states = array();
         $comments = array();
         $this->_comment = false;
         $this->_last = true;

         foreach ($lines as $i => $line) {
            $line = $lines[$i] = rtrim($line);

            if ($coverage[$i + 1]) {
               $states[$i] = 'tested';
               $this->_last = true;
               $code++;
               $covered_size++;
               $covered_code++;
            } elseif ($type = $this->infer($line)) {
               $states[$i] = 'inferred';
               $types[$i] = $type;
               $covered_size++;

               if ($type != 'blank' and $type != 'line noise'
                     and strpos($type, 'comment') === false) {
                  $covered_code++;
                  $code++;
               }
            } else {
               $states[$i] = 'untested';
               $this->_last = false;
               $code++;
            }
         }

         $report = array(
            'file' => $file,
            'size' => $size,
            'code' => $code,
            'coverage_total' => (($size > 0)
               ? sprintf('%.1f', $covered_size / $size * 100)
               : 0),
            'coverage_code' => ($code > 0)
               ? sprintf('%.1f', $covered_code / $code * 100)
               : 0,
         );

         $this->render('file', $file, array(
            'title'  => $name,
            'report' => $report,
            'lines'  => $lines,
            'states' => $states,
            'types'  => $types,
            'pad'    => strlen($size),
         ));

         $this->_total_size += $size;
         $this->_total_code += $code;
         $this->_covered_size += $covered_size;
         $this->_covered_code += $covered_code;
         $this->_reports[$name] = $report;
      }

      protected function render_index() {
         print "   creating index.html\n";

         $this->_reports = array_merge(
            array('TOTAL' => array(
               'size' => $this->_total_size,
               'code' => $this->_total_code,
               'coverage_total' => ($this->_total_size > 0)
                  ? sprintf('%.1f', $this->_covered_size / $this->_total_size * 100)
                  : "0.0",
               'coverage_code'   => ($this->_total_code > 0)
                  ? sprintf('%.1f', $this->_covered_code / $this->_total_code * 100)
                  : "0.0")),
            $this->_reports
         );

         $this->render('index', 'index.html', array(
            'title'   => '',
            'reports' => $this->_reports,
         ));
      }

      protected function render($template, $file, array $data) {
         $view = new View(
            $this->_view_path."$template.thtml",
            $this->_view_path."layout.thtml"
         );

         foreach ($data as $key => $value) {
            $view->set($key, $value);
         }
         $view->set('time', strftime('%a, %d %b %Y %H:%M:%S %z'));
         $output = $view->render();

         if (!file_put_contents("{$this->_target}/$file", $output)) {
            throw new ApplicationError("Could not write file $file");
         }
      }

      protected function infer($line) {
         $line = preg_replace('/\s+/', ' ', trim($line));
         $type = 'line noise';

         if (preg_match('/\*\/[^\'"]*/', $line)) {
            $this->_comment = false;
            $type = 'end of comment';
         } elseif ($this->_last and $this->_comment) {
            return 'in comment';
         } elseif (preg_match('/[^\'"]*\/\*/', $line)) {
            $this->_comment = true;
            $type = 'start of comment';
         }

         if ($this->_last and blank($line)) {
            return 'blank';
         } elseif ($this->_last and !preg_match('/[^ ;(){}<>\/\*#?]/', $line)) {
            return $type;
         } elseif ($this->_last and preg_match('/^(#|\/\/)/', $line)) {
            return 'comment';
         } elseif (preg_match('/^\<\?#.*\?>$/', $line)) {
            return 'comment tag';
         } elseif ($this->_last and preg_match('/^\}? ?(if|else|for|foreach|while|switch|case|default|try|catch) ?[:(\{]?$/', $line)) {
            return 'statement';
         } elseif (!$this->_comment and preg_match('/^(\w+ )?function \w+ ?\(.*\) ?\{?( ?\} ?;)?$/', $line)) {
            return 'function';
         } elseif (!$this->_comment and preg_match('/^(\w+ )?class \w+ ?(extends \w+)? ?\{?( ?\} ?;)?$/', $line)) {
            return 'class';
         } elseif ($this->_last and preg_match('/^(\w+ )?\w+ \$\w+(;| = .+)$/', $line)) {
            return 'property';
         } elseif ($this->_last and preg_match('/^(\$\w+|[\d\.]+|[\'"].*[\'"]) ?[\.,]?$/', $line)) {
            return 'literals';
         } elseif ($this->_last and preg_match('/^(\$\w+ ?= ?array\(|[\'"]?\w+[\'"]? => [\'"$]?\w+[\'"]?,?)$/', $line)) {
            return 'array assignment';
         } elseif ($this->_last and preg_match('/^[\w\s\-\+\.\/,;:!?äöü\'"]*$/u', $line)) {
            return 'text';
         } elseif (preg_match('/(^<[!\/]?\w+|[^?]>$)/', $line)) {
            return 'html';
         } else {
            return false;
         }
      }
   }

   function coverage_graph($percentage) {
      $a = round($percentage);
      $b = round(100 - $a);
      return "<td class=\"rightnb right\">$percentage%</td>\n"
           . "<td class=\"leftnb\"><table class=\"graph\"><tr>\n"
           . "  <td class=\"tested\" style=\"width: ${a}px\"></td>\n"
           . (($percentage == 100) ? ""
           : "  <td class=\"untested\" style=\"width: ${b}px\"></td>\n")
           . "</tr></table></td>\n";
   }
   
?>
