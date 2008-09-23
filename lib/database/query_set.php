<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class QuerySet extends Object implements Iterator, ArrayAccess, Countable
   {
      protected $_mapper;
      protected $_table;
      protected $_statement;
      protected $_objects;

      protected $_position = 0;
      protected $_count;
      protected $_count_all;

      protected $_sql;
      protected $_params = array();
      protected $_options = array();

      protected $_sorted_key;
      protected $_filtered_keys;

      protected $_paginate = false;
      protected $_page = 1;
      protected $_pages = 1;
      protected $_next_page;
      protected $_prev_page;

      function __construct(DatabaseMapper $mapper, array $options=null) {
         $this->_mapper = $mapper;
         $this->_table = $mapper->table;

         $this->_options = array_merge(array(
            'select' => "`{$this->_table}`.*",
            'from'   => "`{$this->_table}`",
            'join'   => null,
            'where'  => null,
            'group'  => null,
            'having' => null,
            'order'  => $this->_mapper->order,
            'limit'  => null,
            'offset' => null,
         ), (array) $options);
      }

      function get_statement() {
         if (!$this->_statement) {
            $this->_statement = $this->_mapper->execute($this->sql, $this->params);
         }

         return $this->_statement;
      }

      function get_objects() {
         if (is_null($this->_statement) or count($this->_objects) != $this->count) {
            $this->_objects = $this->statement->fetch_all_load($this->_mapper->model);
         }

         return $this->_objects;
      }

      function get_count() {
         if (is_null($this->_count)) {
            if ($this->_statement or $this->_options['having']) {
               return $this->row_count;
            } else {
               $current_options = $this->_options;
               $this->replace_select('count(*)');
               $this->order();

               $this->_count = $this->statement->fetch_column();
               $this->_sql = $this->_statement = null;

               $this->_options = $current_options;
            }
         }

         return $this->_count;
      }

      function get_count_all() {
         if ($this->_paginate) {
            if (!$this->_sql) {
               $this->sql;
            }
            return $this->_count_all;
         } else {
            return $this->count;
         }
      }

      function get_row_count() {
         if (is_null($this->_count)) {
            $this->_count = $this->statement->row_count();
         }

         return $this->_count;
      }

      function get_empty() {
         return $this->row_count == 0;
      }

      function get_sql() {
         if ($this->_sql) {
            return $this->_sql;
         }

         $options = &$this->_options;
         $params = array();

         $sql = 'SELECT '.implode(', ', (array) $options['select'])
                .' FROM '.implode(', ', (array) $options['from']);

         if ($join = $options['join']) {
            $sql .= ' '.implode(' ', (array) $join);
         }

         if ($conditions = $options['where']) {
            list($conditions, $where_params) = $this->_mapper->build_condition($conditions);
            if (!blank($conditions)) {
               $sql .= " WHERE $conditions";
               $params = array_merge($params, $where_params);
            }
         }

         if ($group = $options['group']) {
            $sql .= ' GROUP BY '.implode(', ', (array) $group);
         }

         if ($conditions = $options['having']) {
            list($conditions, $having_params) = $this->_mapper->build_condition($conditions);
            if (!blank($conditions)) {
               $sql .= " HAVING $conditions";
               $params = array_merge($params, $having_params);
            }
         }

         if ($order = $options['order']) {
            $sql .= ' ORDER BY '.implode(', ', (array) $order);
         }

         if ($this->_paginate and $size = $this->page_size) {
            $query = new QuerySet($this->_mapper, $this->_options);
            $count = $this->_count_all = $query->count;

            if ($count > $size) {
               $this->_pages = ceil($count / $size);
               $this->_page = max(1, min($this->_pages, intval($_REQUEST['page'])));
               $options['limit'] = $size;
               $options['offset'] = ($this->_page - 1) * $size;
            }
         }

         if ($limit = $options['limit']) { $sql .= " LIMIT $limit"; }
         if ($offset = $options['offset']) { $sql .= " OFFSET $offset"; }

         $this->_sql = $sql;
         $this->_params = $params;

         return $sql;
      }

      function get_params() {
         if (is_null($this->_sql)) {
            $this->sql;
         }

         return $this->_params;
      }

      function get_options() {
         return $this->_options;
      }

      function get_sorted_key() {
         return $this->_sorted_key;
      }

      function get_filtered_keys() {
         return $this->_filtered_keys;
      }

      function get_page() {
         return $this->_page;
      }

      function get_pages() {
         return $this->_pages;
      }

      function get_page_size() {
         return $this->_mapper->page_size;
      }

      function get_next_page() {
         if ($this->_page < $this->_pages) {
            return $this->_page + 1;
         }
      }

      function get_prev_page() {
         if ($this->_page > 1) {
            return $this->_page - 1;
         }
      }

      function get_first() {
         return $this->current();
      }

      function get_last() {
         return $this->objects[$this->count - 1];
      }

      function get_is_first() {
         return $this->_position == 0;
      }

      function get_is_last() {
         return $this->_position == $this->_count - 1;
      }

      function get_all() {
         return $this;
      }

      function reset() {
         $this->_statement = $this->_count = $this->_objects = null;
      }

      function find($id) {
         return $this->where(func_get_args())->order()->first;
      }

      function find_by_sql($sql, array $params=null) {
         if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
         }

         $this->_sql = $sql;
         $this->_params = $params;

         return $this;
      }

      # Handle automatic methods
      function __call($method, $args) {
         if (count($args) == 1 and is_array($args[0])) {
            $args = $args[0];
         }

         # find(_all)_by_* calls
         if (preg_match('/^(find(?:_all)?)_(by|like)_(\w+?)(?:_(and|or)_(\w+))?$/', $method, $match)) {
            list($method, $finder, $equality, $key, $operator, $keys) = $match;

            $finder = ($finder == 'find' ? 'first' : 'all');

            $argc = count($args);

            $equality = ($equality == 'by') ? '=' : 'LIKE';

            if ($operator) {
               $keys = explode("_{$operator}_", $keys);
               array_unshift($keys, $key);
            } else {
               $keys = (array) $key;
            }

            $where = '';
            $op = '';
            foreach ($keys as $key) {
               $where .= "$op`$key` $equality ?";
               $conditions[] = array_shift_arg($args);
               $op = ' '.strtoupper($operator).' ';
            }

            array_unshift($conditions, "($where)");

            if ($args) {
               throw new ApplicationError('Too many arguments');
            }

            return $this->where($conditions)->$finder;

         } elseif (substr($method, 0, 8) == 'replace_') {
            return $this->replace(substr($method, 8), $args);
         } elseif (substr($method, 0, 6) == 'merge_') {
            return $this->merge(substr($method, 6), $args);
         } elseif (in_array($method, array('group', 'order', 'limit', 'offset'))) {
            return $this->replace($method, $args);
         } else {
            return $this->merge($method, $args);
         }
      }

      # Replace given options
      function replace($key, $options) {
         if (array_key_exists($key, $this->_options)) {
            $this->_sql = null;
            $this->_options[$key] = $options;
            return $this;
         } else {
            throw new UndefinedMethod($this, $key);
         }
      }

      # Merge given options
      function merge($key, $options) {
         if (array_key_exists($key, $this->_options)) {
            $this->_sql = null;
            $this->_options[$key] = array_merge(
               (array) $this->_options[$key],
               (array) $options
            );
            return $this;
         } else {
            throw new UndefinedMethod($this, $key);
         }
      }

      # Return a hash of the given keys and values, with an optional blank value first
      # (useful for building drop-down boxes)
      function map($key='id', $value='name', $blank=true) {
         if ($blank === true) {
            $map = array(null => '');
         } elseif ($blank !== false) {
            $map = array(null => $blank);
         } else {
            $map = array();
         }

         foreach ($this->objects as $object) {
            $map[$object->$key] = $object->$value;
         }

         return $map;
      }

      # Return an array with all values for the given key,
      # or an array of hashes if multiple keys are passed
      function collect($key) {
         $keys = func_get_args();
         $this->replace_select($keys);

         $values = array();
         foreach ($this->objects as $object) {
            if (count($keys) == 1) {
               $values[] = $object->$key;
            } else {
               $values[] = array_get($object->attributes, $keys);
            }
         }

         return $values;
      }

      # Sort the query set by the current request parameters
      function get_sorted() {
         if ($this->_mapper->attributes[$sort = $_REQUEST['sort']]) {
            $this->_sorted_key = $sort;
            $this->order("`{$this->_table}`.`$sort`".(isset($_REQUEST['desc']) ? ' DESC' : ' ASC'));
         }

         return $this;
      }

      # Filter the query set by the current request parameters
      function get_filtered() {
         if (is_array($filter = $_REQUEST['filter'])) {
            foreach ($filter as $key => $value) {
               if (substr($key, -5) == '_like') {
                  $key = substr($key, 0, -5);
                  $like = true;
               } else {
                  $like = false;
               }

               if ($this->_mapper->attributes[$key]) {
                  $keys[] = $key;
                  if ($like) {
                     $conditions["`{$this->_table}`.`$key` LIKE ?"] = "%$value%";
                  } else {
                     $conditions["`{$this->_table}`.`$key` = ?"] = $value;
                  }
               }
            }

            if ($keys) {
               $this->_filtered_keys = $keys;
               $this->where($conditions);
            }
         }

         return $this;
      }

      # Paginate the query set by the current request parameters
      function get_paginated() {
         $this->_paginate = true;
         $this->_sql = null;
         return $this;
      }

      # Iterator implementation

      function rewind() {
         $this->_position = 0;
      }

      function current() {
         if ($object = $this->_objects[$this->_position]) {
            return $object;
         } else {
            return $this->_objects[$this->_position] = $this->statement->fetch_load($this->_mapper->model);
         }
      }

      function key() {
         return $this->_position;
      }

      function next() {
         $this->_position++;
      }

      function valid() {
         return $this->_position < $this->row_count;
      }

      # ArrayAccess implementation

      function offsetExists($key) {
         return $key < $this->count;
      }

      function offsetGet($key) {
         return $this->objects[$key];
      }

      function offsetSet($key, $value) {
         return $this->objects[$key] = $value;
      }

      function offsetUnset($key) {
         unset($this->objects[$key]);
      }

      # Countable implementation

      function count($conditions=null) {
         if ($conditions) {
            $this->where(func_get_args());
         }

         return $this->count;
      }
   }

?>
