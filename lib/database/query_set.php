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
      protected $_statement;
      protected $_objects;
      protected $_preload;

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

      function __construct(DatabaseMapper $mapper, array $options=null, array $params=null, array $preload=null) {
         $this->_mapper = $mapper;

         $this->_options = array_merge(array(
            'select' => "`{$this->table}`.*",
            'from'   => "`{$this->table}`",
            'joins'  => null,
            'where'  => null,
            'group'  => null,
            'having' => null,
            'order'  => $this->_mapper->order,
            'limit'  => null,
            'offset' => null,
         ), (array) $options);

         if (is_array($params)) {
            $this->_params = $params;
         }

         if (is_array($preload)) {
            $this->_preload = $preload;
         }
      }

      function __toString() {
         return parent::__toString($this->model);
      }

      function inspect() {
         return parent::inspect(array(
            'model' => $this->model,
            'sql'    => $this->sql,
         ));
      }

      function to_json() {
         return '['.implode(",\n", array_collect($this->objects, 'to_json')).']';
      }

      function to_xml() {
         return implode("\n", array_collect($this->objects, 'to_xml'));
      }

      function copy() {
         $class = get_class($this);
         return new $class($this->_mapper, $this->_options, $this->_params, $this->_preload);
      }

      function get_model() {
         return $this->_mapper->model;
      }

      function get_table() {
         return $this->_mapper->table;
      }

      function fetch() {
         if ($this->model) {
            if ($object = $this->statement->fetch_load($this->model)) {
               if (is_array($this->_preload)) {
                  $object->load($this->_preload);
               }

               return $object;
            }
         } else {
            return $this->statement->fetch();
         }
      }

      function fetch_all() {
         if ($this->model) {
            if ($objects = $this->statement->fetch_all_load($this->model)) {
               if (is_array($this->_preload)) {
                  foreach ($objects as $object) {
                     $object->load((array) $this->_preload);
                  }
               }

               return $objects;
            }
         } else {
            return $this->statement->fetch_all();
         }
      }

      function fetch_raw() {
         return $this->statement->fetch();
      }

      function fetch_all_raw() {
         return $this->statement->fetch_all();
      }

      function fetch_column($key) {
         $this->replace_select($key);
         if ($object = $this->fetch()) {
            return getf($object, $key);
         }
      }

      function preload(array $data) {
         $this->_preload = array_merge((array) $this->_preload, $data);
         return $this;
      }

      function get_statement() {
         if (!$this->_statement) {
            $this->_statement = $this->_mapper->execute($this->sql, $this->params);
         }

         return $this->_statement;
      }

      function get_objects() {
         if ($this->_statement) {
            # Statement was already executed, load all remaining objects
            $current_pos = $this->_position;
            while ($this->valid()) {
               $this->next();
            }
            $this->_position = $current_pos;
         } else {
            # Statement wasn't executed yet, load all objects in one run
            $this->_objects = $this->fetch_all();
         }

         return (array) $this->_objects;
      }

      function get_count($conditions=null) {
         if (is_null($this->_count) or $conditions) {
            if ($this->_statement and !$conditions) {
               $this->_count = count($this->objects);
            } else {
               $current_options = $this->_options;
               $current_sql = $this->_sql;

               $this->order();

               if ($this->_paginate or $this->_options['group'] or $this->_options['having'] or is_numeric($this->_options['limit']) or is_numeric($this->_options['offset'])) {
                  $this->_sql = "SELECT count(*) FROM ({$this->sql}) internal_count_alias";
               } else {
                  $this->replace_select('count(*)');
               }

               if ($conditions) {
                  $conditions = func_get_args();
                  call_user_func_array(array($this, 'where'), $conditions);
               }

               $count = round($this->statement->fetch_column());

               $this->_options = $current_options;
               $this->_sql = $current_sql;
               $this->_statement = null;

               if ($conditions) {
                  return $count;
               } else {
                  $this->_count = $count;
               }
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

      function get_empty() {
         return count($this->objects) == 0;
      }

      function get_sql() {
         if ($this->_sql) {
            return $this->_sql;
         }

         $options = &$this->_options;
         $params = array();

         $select = array();
         foreach ((array) $options['select'] as $column => $alias) {
            if (is_numeric($column)) {
               $select[] = $alias;
            } else {
               $select[] = "$column AS $alias";
            }
         }

         $sql = 'SELECT '.implode(', ', $select);

         $from = array();
         foreach ((array) $options['from'] as $table => $alias) {
            if (is_numeric($table)) {
               $from[] = $alias;
            } else {
               $from[] = "$table $alias";
            }
         }

         $sql .= ' FROM '.implode(', ', $from);

         if ($joins = $options['joins']) {
            foreach ($joins as $join) {
               if (is_array($join)) {
                  $sql .= ' '.strtoupper($join['type']).' JOIN';

                  $table = $join['table'];
                  $alias = null;

                  $spaces = substr_count($table, ' ');
                  if (substr_count($table, '.') > 0) {
                     $sql .= " $table";
                  } elseif ($spaces == 1) {
                     list($table, $alias) = explode(' ', $table, 2);
                     $sql .= " `$table` AS `$alias`";
                  } elseif ($spaces == 0) {
                     $sql .= " `$table`";
                  } else {
                     $sql .= " $table";
                  }

                  $conditions = $join['conditions'];
                  if (is_null($conditions)) {
                     continue;
                  } else {
                     if ($model = classify($table) and is_subclass_of($model, ActiveRecord)) {
                        $mapper = DB($model);
                        $builder = 'build_condition';
                     } else {
                        $mapper = $this->_mapper;
                        $builder = 'build_condition_without_scope';
                     }

                     if ($alias) {
                        $table = $mapper->table;
                        $mapper->table = $alias;
                     }

                     list($conditions, $join_params) = call_user_func(array($mapper, $builder), $conditions);

                     if ($alias) {
                        $mapper->table = $table;
                     }

                     $params = array_merge($params, $join_params);
                  }

                  $mode = strtoupper($join['mode']);
                  $sql .= " $mode ($conditions)";
               } else {
                  $sql .= " $join";
               }
            }
         }

         list($conditions, $where_params) = $this->_mapper->build_condition($options['where']);
         if (!blank($conditions)) {
            $sql .= " WHERE $conditions";
            $params = array_merge($params, $where_params);
         }

         if ($group = $options['group']) {
            $sql .= ' GROUP BY '.$this->format_columns((array) $group);
         }

         if ($conditions = $options['having']) {
            list($conditions, $having_params) = $this->_mapper->build_condition_without_scope($conditions);
            if (!blank($conditions)) {
               $sql .= " HAVING $conditions";
               $params = array_merge($params, $having_params);
            }
         }

         if ($order = $options['order']) {
            $sql .= ' ORDER BY '.$this->format_columns((array) $order, 'order');
         }

         if ($this->_paginate and $size = $this->page_size) {
            $query = new QuerySet($this->_mapper, $this->_options);
            $count = $this->_count_all = $query->count;

            if ($count > $size) {
               $this->_pages = ceil($count / $size);
               $this->_page = max(1, min($this->_pages, round(Dispatcher::$params['page'])));
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

      protected function format_columns($columns, $method=null) {
         foreach ($columns as $key => $column) {
            if ($method == 'order') {
               list($column, $order) = explode(' ', $column, 2);
            }

            if ($this->_mapper->attributes[$column]) {
               $column = "`{$this->_mapper->table}`.`$column`";
            }

            if ($method == 'order' and $order) {
               $column .= " $order";
            }

            $columns[$key] = $column;
         }

         return implode(', ', $columns);
      }

      function get_params() {
         if (is_null($this->_sql)) {
            $this->get_sql();
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

      function get_page_start() {
         return $this->_mapper->page_size * ($this->page - 1) + 1;
      }

      function get_page_end() {
         return min($this->count_all, $this->page_start + $this->page_size - 1);
      }

      function get_first() {
         if ($this->_objects) {
            return $this->_objects[0];
         } else {
            $copy = $this->copy()->limit(1);
            if ($copy->valid()) {
               return $copy->current();
            }
         }
      }

      function get_last() {
         $this->objects;
         return $this->objects[$this->count - 1];
      }

      function get_is_first() {
         return $this->_position == 0;
      }

      function get_is_last() {
         return $this->_position == $this->count - 1;
      }

      function get_all() {
         return $this;
      }

      function reset() {
         $this->_statement = $this->_count = $this->_objects = null;
      }

      function find($conditions) {
         if (!blank($conditions)) {
            return $this->copy()->where(func_get_args())->order()->first;
         }
      }

      function find_by_sql($sql, $params=null) {
         if (!is_array($params)) {
            $params = array_slice(func_get_args(), 1);
         }

         $this->_sql = $sql;
         $this->_params = $params;

         return $this;
      }

      function destroy_all($conditions=null) {
         if (blank($conditions)) {
            throw new ApplicationError("Not unless you really want to");
         } elseif ($conditions and $conditions !== true) {
            $this->where(func_get_args());
         }

         $status = false;
         foreach ($this->objects as $object) {
            $status = $object->destroy();
            $object->dispose(0);
         }

         return $status;
      }

      # Automatic getters for boolean filters
      #   e.g.: DB(User)->activated
      #         DB(User)->not_activated
      #
      protected function __get_custom($key) {
         if (substr($key, 0, 4) == 'not_') {
            $key = substr($key, 4);
            $enabled = false;
         } else {
            $enabled = true;
         }

         if ($column = $this->_mapper->attributes[$key] and $column['type'] == 'bool') {
            return $this->where($enabled
               ? "$key = 1"
               : "(`$key` = 0 OR `$key` IS NULL)"
            );
         } else {
            return false;
         }
      }

      # Handle automatic methods
      function __call($method, $args) {
         if (count($args) == 1 and is_array($args[0])) {
            $args = $args[0];
         }

         # find(_all)_by_* calls
         if (preg_match('/^(find(?:_all)?)_(by|like)_(\w+?)(?:_(and|or)_(\w+))?$/', $method, $match)) {
            list($method, $finder, $equality, $key, $operator, $keys) = $match;

            if ($finder == 'find_all') {
               $finder = 'where';
            }

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
               $conditions[] = array_shift_arg($args, "Missing value at '$where'");
               if ($op == '') {
                  $op = ' '.strtoupper($operator).' ';
               }
            }

            array_unshift($conditions, "($where)");

            if ($args) {
               throw new ApplicationError('Too many arguments');
            }

            return call_user_func_array(array($this, $finder), $conditions);

         } elseif (substr($method, 0, 8) == 'replace_') {
            # replace_* calls replace the given SQL options
            return $this->replace(substr($method, 8), $args);

         } elseif (substr($method, 0, 6) == 'merge_') {
            # merge_* calls merge the given SQL options
            return $this->merge(substr($method, 6), $args);

         } elseif (in_array($method, array('order', 'group'))) {
            # order() and group() replace by default
            return $this->replace($method, $args);

         } elseif (in_array($method, array('limit', 'offset'))) {
            # limit() and offset() replace by default, and only have one argument
            return $this->replace($method, $args[0]);

         } elseif (in_array($method, array('sum', 'avg', 'min', 'max'))) {
            # shortcuts for aggregate functions
            $this->replace_select("$method({$args[0]})");
            $value = $this->statement->fetch_column();
            $this->reset();
            return $value;

         } elseif (preg_match('/^(?:(left|right|inner|outer|natural)_)?join(_using)?$/', $method, $match)) {
            # Add a join
            if (count($args) > 1) {
               # Add join with custom syntax
               $this->_options['joins'][] = array(
                  'type'       => any($match[1], 'left'),
                  'table'      => array_shift($args),
                  'conditions' => count($args) > 1 ? $args : $args[0],
                  'mode'       => $match[2] ? 'using' : 'on',
               );
            } else {
               # Add literal JOIN
               $this->_options['joins'][] = $args[0];
            }

            return $this;

         } elseif (array_key_exists($method, $this->_options)) {
            # Merge by default
            return $this->merge($method, $args);

         } elseif (array_key_exists($method, $this->_mapper->attributes)) {
            # Filter by key
            array_unshift($args, $method);
            return $this->merge('where', $args);

         } else {
            throw new UndefinedMethod($this, $method);
         }
      }

      # Replace given options
      function replace($key, $options) {
         if (array_key_exists($key, $this->_options)) {
            if ($options === array(null) or $options === array('')) {
               $options = null;
            }

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

      # Exclude the given keys from the query
      function without($keys) {
         if (!$keys) {
            return $this;
         } elseif (!is_array($keys)) {
            $keys = func_get_args();
         }

         $new_keys = array();
         foreach ($this->_mapper->attributes as $key => $column) {
            if (!in_array($key, $keys)) {
               $new_keys[] = "`{$this->table}`.$key";
            }
         }

         return $this->replace('select', array_unique(array_merge(
            $new_keys, array_without((array) $this->_options['select'], "`{$this->table}`.*")
         )));
      }

      # Exclude all TEXT and BLOB columns
      function without_blobs() {
         $keys = array();
         foreach ($this->_mapper->attributes as $key => $column) {
            if ($column['type'] == 'text' or $column['blob'] == 'blob') {
               $keys[] = $key;
            }
         }

         return $this->without($keys);
      }

      # Return an array with all values for the given key,
      # or an array of hashes if multiple keys are passed
      function collect($keys) {
         if (!is_array($keys)) {
            $keys = func_get_args();
         }
         $this->replace_select($keys);

         $attributes = array();
         foreach ($keys as $i => $key) {
            if (is_array($key)) {
               $attributes[] = array_shift(array_values($key));
            } else {
               $key = trim($key);
               if (strtoupper(substr($key, 0, 9)) == 'DISTINCT ') {
                  $attributes[] = substr($key, 9);
               } else {
                  $attributes[] = $key;
               }
            }
         }

         $values = array();
         foreach ($this->objects as $object) {
            if (count($attributes) > 1) {
               $values[] = array_get(
                  is_array($object) ? $object : $object->attributes,
                  $attributes
               );
            } else {
               $values[] = getf($object, $attributes[0]);
            }
         }

         return $values;
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
            $map[getf($object, $key)] = getf($object, $value);
         }

         return $map;
      }

      # Check if the given key exists in the query
      function has_key($key) {
         if (isset($this->_mapper->attributes[$key])) {
            return 'table';
         } else {
            # If the attribute doesn't exist in the table, look if an aliased column exists
            foreach ((array) $this->_options['select'] as $key => $value) {
               if (!is_numeric($key) and $value == $key) {
                  return 'alias';
               }
            }
         }
         
         return false;
      }

      # Sort the query set by the current request parameters
      function sorted() {
         $sort_key = Dispatcher::$params['sort'];
         $type = $this->has_key($sort_key);

         if ($type == 'table') {
            $sort_key = "`{$this->table}`.`$sort_key`";
         } elseif ($type == 'alias') {
            $sort_key = "`$sort_key`";
         } else {
            return $this;
         }

         $this->_sorted_key = $sort_key;
         $this->order($sort_key.(isset(Dispatcher::$params['desc']) ? ' DESC' : ' ASC'));

         return $this;
      }

      # Filter the query set by the current request parameters
      function filtered() {
         if (is_array($filter = Dispatcher::$params['filter'])) {
            $keys = array();

            foreach ($filter as $key => $value) {
               $value = urldecode($value);

               if (substr($key, -5) == '_like') {
                  $key = substr($key, 0, -5);
                  $like = true;
               } else {
                  $like = false;
               }

               $type = $this->has_key($key);
               if ($type == 'table') {
                  $filter_key = "`{$this->table}`.`$key`";
               } elseif ($type == 'alias') {
                  $filter_key = "`$key`";
               } else {
                  continue;
               }

               $keys[] = $key;
               if ($like) {
                  $conditions["$filter_key LIKE ?"] = "%$value%";
               } else {
                  $conditions["$filter_key = ?"] = $value;
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
      function paginated() {
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
         } elseif ($object = $this->fetch()) {
            return $this->_objects[$this->_position] = $object;
         }
      }

      function key() {
         return $this->_position;
      }

      function next() {
         $this->_position++;
      }

      function valid() {
         return !is_null($this->current());
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
         return $this->get_count(func_get_args());
      }
   }

?>
