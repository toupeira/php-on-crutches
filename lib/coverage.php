<?
/*
  PHP on Crutches - Copyright 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License. See COPYING for details.

  $Id$
*/

  class CoverageReport extends Object
  {
    private $files;
    private $target;
    private $include;
    private $exclude;
    private $reports;
    private $view;

    private $total_size = 0;
    private $total_code = 0;
    private $covered_size = 0;
    private $covered_code = 0;
    private $comment = false;
    private $last = true;

    function __construct($coverage, $target=null, $include=null, $exclude=null) {
      $this->files = $coverage;
      $this->target = $target;

      if (is_array($include)) {
        $this->include = $include;
      } else {
        $this->include = APP;
      }

      $this->exclude = (array) $exclude;

      $this->view = new View();
    }

    function generate() {
      print "Generating report in {$this->target}...\n";

      ksort($this->files);
      $filtered = array();

      foreach ($this->files as $file => $lines) {
        if (is_file($file)) {
          $skip = false;
          foreach ((array) $this->exclude as $base_path) {
            if (strpos($file, $base_path) === 0) {
              $skip = true;
            }
          }

          if ($skip) continue;

          foreach ((array) $this->include as $base_path) {
            if (strpos($file, $base_path) === 0) {
              $filtered[$file] = $lines;
            }
          }
        }
      }

      $this->files = $filtered;

      print pluralize(count($this->files), 'file', 'files')." found:\n";

      if (file_exists($this->target)) {
        print "Error: Target path {$this->target} already exists.\n";
        return false;
      } elseif (!mkdir($this->target)) {
        print "Error: Could not create target path $target.\n";
        return false;
      }

      foreach ($this->files as $path => $coverage) {
        $this->render_file($path, $coverage);
      }

      $this->render_index();
      print "Done.\n\n";

      return true;
    }

    protected function render_file($path, $coverage) {
      $name = substr($path, strlen(ROOT));

      $file = str_replace('/', '-', str_replace('.', '_', $name)).'.html';
      print "  creating $file\n";
      $lines = file($path) or
        raise("Could not open file $path.");

      $size = count($lines);
      $code = 0;
      $covered_size = 0;
      $covered_code = 0;

      foreach ($coverage as $i => $value) {
        if ($i > 0 and $i < $size) {
          $covered_size++;
          $covered_code++;
        }
      }

      $states = array();
      $comments = array();
      $this->comment = false;
      $this->last = true;

      foreach ($lines as $i => $line) {
        $line = $lines[$i] = rtrim($line);

        if ($coverage[$i + 1]) {
          $states[$i] = 'tested';
          $this->last = true;
          $code++;
        } elseif ($type = $this->infer($line)) {
          $states[$i] = 'inferred';
          $types[$i] = $type;
          $covered_size++;

          if ($type != 'blank' and strpos($type, 'comment') === false) {
            $covered_code++;
            $code++;
          }
        } else {
          $states[$i] = 'untested';
          $this->last = false;
          $code++;
        }
      }

      $report = array(
        'file' => $file,
        'size' => $size,
        'code' => $code,
        'coverage_total' => sprintf('%.1f', $covered_size / $size * 100),
        'coverage_code'  => sprintf('%.1f', $covered_code / $code * 100),
      );

      $this->view->set('title', $name);
      $this->view->set('report', $report);
      $this->view->set('lines', $lines);
      $this->view->set('states', $states);
      $this->view->set('types', $types);
      $this->view->set('pad', strlen($size));
      $this->render('file', $file);

      $this->total_size += $size;
      $this->total_code += $code;
      $this->covered_size += $covered_size;
      $this->covered_code += $covered_code;
      $this->reports[$name] = $report;
    }

    protected function render_index() {
      print "  creating index.html\n";

      $this->reports = array_merge(
        array('TOTAL' => array(
          'size' => $this->total_size,
          'code' => $this->total_code,
          'coverage_total' => ($this->total_size > 0)
            ?  sprintf('%.1f', $this->covered_size / $this->total_size * 100)
            : "0.0",
          'coverage_code'  => ($this->total_code > 0)
            ?  sprintf('%.1f', $this->covered_code / $this->total_code * 100)
            : "0.0")),
        $this->reports
      );

      $this->view->set('title', "");
      $this->view->set('reports', $this->reports);

      $this->render('index', 'index.html');
    }

    protected function render($template, $file) {
      $this->view->set('time', strftime('%a, %d %b %Y %H:%M:%S %z'));
      $this->view->layout = LIB."views/coverage/layout.thtml";
      $output = $this->view->render(LIB."views/coverage/$template.thtml");

      file_put_contents("{$this->target}/$file", $output) or
        raise("Could not write file $file.");
    }

    protected function infer($line) {
      $line = preg_replace('/\s+/', ' ', trim($line));
      $type = 'line noise';

      if (preg_match('/\*\/[^\'"]*/', $line)) {
        $this->comment = false;
        $type = 'end of comment';
      } elseif ($this->last and $this->comment) {
        return 'in comment';
      } elseif (preg_match('/[^\'"]*\/\*/', $line)) {
        $this->comment = true;
        $type = 'start of comment';
      }

      if ($this->last and empty($line)) {
        return 'blank';
      } elseif ($this->last and !preg_match('/[^ ;(){}<>\/\*#?]/', $line)) {
        return $type;
      } elseif ($this->last and preg_match('/^(#|\/\/)/', $line)) {
        return 'comment';
      } elseif (preg_match('/^\<\?#.*\?>$/', $line)) {
        return 'comment tag';
      } elseif ($this->last and preg_match('/^\}? ?(if|else|for|foreach|while|switch|case|default|try|catch) ?[:(\{]?$/', $line)) {
        return 'statement';
      } elseif (!$this->comment and preg_match('/^(\w+ )?function \w+ ?\(.*\) ?\{?( ?\} ?;)?$/', $line)) {
        return 'function';
      } elseif (!$this->comment and preg_match('/^(\w+ )?class \w+ ?(extends \w+)? ?\{?( ?\} ?;)?$/', $line)) {
        return 'class';
      } elseif ($this->last and preg_match('/^(\w+ )?\w+ \$\w+(;| = .+)$/', $line)) {
        return 'property';
      } elseif ($this->last and preg_match('/^(\$\w+|[\d\.]+|[\'"].*[\'"]) ?[\.,]?$/', $line)) {
        return 'literals';
      } elseif ($this->last and preg_match('/^\$\w+ ?= ?array\($/', $line)) {
        return 'array assignment';
      } elseif (preg_match('/(^<[!\/]?\w+|[^?]>$)/', $line)) {
        return 'html';
      } else {
        return false;
      }
    }
  }

  function coverage_graph($percentage) {
    $a = intval($percentage);
    $b = intval(100 - $a);
    return "<td class=\"rightnb right\">$percentage%</td>\n"
         . "<td class=\"leftnb\"><table class=\"graph\"><tr>\n"
         . "  <td class=\"tested\" style=\"width: ${a}px\"></td>\n"
         . (($percentage == 100) ? ""
         : "  <td class=\"untested\" style=\"width: ${b}px\"></td>\n")
         . "</tr></table></td>\n";
  }
  
?>
