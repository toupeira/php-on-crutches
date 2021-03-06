<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class DocParseError extends SyntaxError {}

   class DocParser extends Object
   {
      const SYNTAX = '{
         (?:
            ^(\s*)                                                # $indent
            ((?:abstract\s+|static\s+|final\s+)*)                 # $flags
            (?:(private|protected|public)\s+)?                    # $visibility
            (?:
               # Class definitions
               class\s+
                  ([\w_]+)                                        # $class
                  (?:\s+extends\s+([\w_]+))?                      # $parent
                  (?:\s+implements\s+([\w\s_,]+))?                # $interfaces
                  (\s*\{.*\};?$)?|                                # $class_inline

               # Function and method definitions
               function\s+
                  ([\w_]+)\s*                                     # $function
                  \(([^\{]*)\)                                    # $arguments
                  (\s*(?:\{.*\})?;?$)?|                           # $function_inline

               # Properties
               \$([\w_]+)\s*                                      # $property
                  (?:;|=\s*(.+)?\s*;\s*$|(.*[^;])$)               # $property_value, $property_value_continued
            )|

            # Class constants
            const\s+
               ([\w_]+)\s*=\s*                                    # $class_constant
               (?:(.+);\s*$|.*[^;]$)|                             # $class_constant_value

            # Definitions using define() or define_default()
            define(?:_default)?\([\'"]([^\'"]+)[\'"],\s*(.+)\);   # $constant, $constant_value
         )
      }x';

      protected $_path;
      protected $_line;
      protected $_verbose;

      protected $_classes;
      protected $_functions;
      protected $_constants;
      protected $_file_comment;

      protected $_comment;
      protected $_comment_indent;
      protected $_source;

      protected $_class;
      protected $_class_indent;
      protected $_function;
      protected $_function_indent;
      protected $_function_scope;

      function __construct($path) {
         $this->_path = $path;
      }

      function get_path() {
         return (string) $this->_path;
      }

      function get_classes() {
         return (array) $this->_classes;
      }

      function get_functions() {
         return (array) $this->_functions;
      }

      function get_constants() {
         return (array) $this->_constants;
      }

      function get_comment() {
         return (string) $this->_file_comment;
      }

      function parse($verbose=false) {
         $this->_verbose = $verbose;

         $file = fopen($this->_path, 'r');
         $this->_line = 1;
         while (!feof($file)) {
            $this->parse_line(rtrim(fgets($file)));
            $this->_line++;
         }

         fclose($file);

         return $this->_classes or $this->_functions or $this->_constants;
      }

      protected function log($action, $type=null, $name=null) {
         if ($this->_verbose) {
            print sprintf("    %5d:  %8s  %8s  %s\n", $this->_line, $action, $type, $name);
         }
      }

      protected function error($message) {
         throw new DocParseError($message, 0, 0, $this->_path, $this->_line);
      }

      protected function parse_line($line) {
         if ($line[0] == '#' or $line == '<?') {
            #
            # Skip comments without indent and PHP tags
            #
            return;
         } elseif (!$this->_function and preg_match('/^(\s+)#(?:\s*(.+)$)?/', $line, $match)) {
            #
            # Add comment text
            #
            return $this->add_comment($match[2], mb_strlen($match[1]));
         } elseif (!blank($line) and is_null($this->_file_comment)) {
            #
            # Add a file comment
            #
            if (count($this->_comment) >= 3 and $this->_comment[0] == '' and last($this->_comment) == '') {
               $this->log("adding", "file comment");
               $this->_file_comment = implode("\n", array_slice($this->_comment, 1, -1));
            } else {
               $this->_file_comment = false;
            }
            return $this->parse_line($line);
         } elseif (preg_match(self::SYNTAX, $line, $match)) {
            #
            # Start a definition
            #
            $this->add_definition($match);
         } elseif ($this->_class and $this->is_indent($line, $this->_class_indent)) {
            #
            # End class
            #
            $this->log("closing", "class", $this->_class);
            $this->_class = null;
         } elseif ($this->_function and $this->is_indent($line, $this->_function_indent)) {
            #
            # End function
            #
            if ($this->_class) {
               $this->log("closing", "method", "{$this->_class}#{$this->_function}()");
            } else {
               $this->log("closing", "function", "{$this->_function}()");
            }
            $this->add_source();
            $this->_function = null;
         }

         if ($this->_function) {
            $this->_source[] = $line;
         }

         if (!is_null($this->_file_comment)) {
            # Reset the current comment
            $this->_comment = null;
         }
      }

      protected function is_indent($line, $indent) {
         return mb_substr($line, 0, $indent + 1) == str_repeat(' ', $indent).'}';
      }

      protected function split($text, $sep='\s+') {
         if (is_array($text)) {
            return $text;
         } elseif (blank($text)) {
            return array();
         } else {
            return (array) preg_split("/$sep/", trim($text));
         }
      }

      protected function add_definition($match) {
         # Get values from matched Regex
         list(
            $match,
            $indent, $flags, $visibility,
            $class, $parent, $interfaces, $class_inline,
            $function, $arguments, $function_inline,
            $property, $property_value, $property_value_continued,
            $class_constant, $class_constant_value,
            $constant, $constant_value
         ) = $match;

         $data = array(
            'comment'    => implode("\n", (array) $this->_comment),
            'visibility' => any($visibility, 'public'),
         );

         foreach ($data['flags'] = $this->split($flags) as $flag) {
            $data[$flag] = true;
         }

         $scope = $data['visibility'].'_'
                . ($data['static'] ? 'class' : 'instance').'_';

         if ($class) {
            #
            # Add a class definition
            #
            $block = 'class';
            $data['parent'] = $parent;
            $data['interfaces'] = $this->split($interfaces, ',\s*');
         } elseif ($function) {
            #
            # Add a function definition
            #
            $block = 'function';
            $scope .= 'methods';
            $data['arguments'] = $this->split($arguments, ',\s*');
         } elseif ($class_constant) {
            #
            # Add a class constant
            #
            if ($this->_class) {
               $this->log("adding", "constant", "{$this->_class}::$class_constant");
               $data['value'] = any($class_constant_value, '...');
               unset($data['visiblity']);
               $this->_classes[$this->_class]['constants'][$class_constant] = $data;
            } else {
               $this->error("Class constant definition outside class");
            }
         } elseif ($property) {
            #
            # Add a class property
            #
            if (!$visibility and !$flags) {
               # Ignore variable assignments without flags or a visibility keyword
               return;
            } elseif ($this->_class) {
               $this->log("adding", "property", "{$this->_class}#$property");
               $scope .= 'properties';
               $data['value'] = ($property_value_continued ? '...' : $property_value);
               $this->_classes[$this->_class][$scope][$property] = $data;
            } elseif ($visibility or $data['abstract'] or $data['final']) {
               $this->error("Property definition outside class");
            }
         } elseif ($constant) {
            #
            # Add a constant
            #
            $this->log("adding", "constant", $constant);
            $data['value'] = $constant_value;
            unset($data['visiblity']);
            $this->_constants[$constant] = $data;
         } else {
            $this->error("Invalid input '$match'");
         }

         if ($block) {
            # Close the block
            $data['closed'] = (bool) ${$block.'_inline'};
            $this->start_block($block, $scope, $$block, mb_strlen($indent), $data);
         }
      }

      protected function start_block($type, $scope, $name, $indent, $data) {
         if ($this->{'_'.$type}) {
            $this->error("Nested $type definition for '$name'");
         }

         if ($type == 'class') {
            $this->log("starting", "class", $name);
            $this->_classes[$name] = $data;
         } elseif ($type == 'function') {
            $this->_source = null;

            if ($this->_class) {
               $this->log("starting", "method", "{$this->_class}#$name()");
               $this->_function_scope = $scope;
               $this->_classes[$this->_class]['methods'][$name] = $data;
               $this->_classes[$this->_class][$scope][$name] = $data;
            } else {
               $this->log("starting", "function", "$name()");
               $this->_functions[$name] = $data;
            }
         } else {
            throw new ValueError($type);
         }

         if (array_delete($data, 'closed')) {
            $this->log("closing", "$type", "$name".($type == 'function' ? '()' : ''));
         } else {
            $this->{'_'.$type} = $name;
            $this->{'_'.$type.'_indent'} = $indent;
         }

         if (!$this->_verbose) {
            print ".";
         }
      }

      protected function add_comment($text, $indent=0) {
         if (!$this->_comment or $this->_comment_indent != $indent) {
            $this->_comment = null;
            $this->_comment_indent = $indent;
         }

         $this->_comment[] = $text;
      }

      protected function add_source() {
         $source = array();
         foreach ((array) $this->_source as $line) {
            if (blank(mb_substr($line, 0, $this->_function_indent))) {
               # Remove the indent
               $source[] = mb_substr($line, $this->_function_indent);
            } else {
               # Return original source if the indent isn't blank
               $source = (array) $this->_source;
               break;
            }
         }

         $source = implode("\n", $source)."\n}";

         if ($this->_class) {
            $this->_classes[$this->_class][$this->_function_scope][$this->_function]['source'] = $source;
         } else {
            $this->_functions[$this->_function]['source'] = $source;
         }

         $this->_source = null;
      }
   }

?>
