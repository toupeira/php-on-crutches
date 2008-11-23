<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class Model extends Object
   {
      # The model mapper
      protected $_mapper;

      # Model attributes
      protected $_attributes = array();

      # Virtual attributes
      protected $_virtual_attributes = array();

      # Changed attributes
      protected $_changed_attributes = array();

      # Read-only attributes can't be changed
      protected $_readonly = array();
      # Protected attributes can only be set explicitly
      protected $_protected = array();
      # Attributes which should not be automatically trimmed
      protected $_skip_trim = array();

      # List of error messages
      protected $_errors = array();

      # Column to use as string representation
      protected $_display_column;

      protected $_new_record = true;

      function __construct(array $attributes=null, array $defaults=null) {
         # Add virtual attributes
         foreach ((array) $this->_virtual_attributes as $key) {
            $this->add_virtual($key, null);
         }

         # Set default values
         $this->set_attributes(array_merge(
            (array) $attributes,
            (array) $defaults
         ));
      }

      function __toString() {
         if ($key = $this->_display_column) {
            return $this->$key;
         } else {
            foreach (array('title', 'name', 'username', 'filename', 'key', 'id') as $key) {
               $getter = any(
                  method_exists($this, "get_$key"),
                  array_key_exists($key, $this->_attributes)
               );

               if ($getter and $value = $this->$key) {
                  return $value;
               }
            }
         }

         return parent::__toString();
      }

      function inspect() {
         return parent::inspect($this->attributes);
      }

      function to_param() {
         return $this->slug;
      }

      function to_params($action=null) {
         return array(
            'controller' => tableize(get_class($this)),
            'action'     => any($action, 'show'),
            'id'         => $this->to_param(),
         );
      }

      function to_json() {
         return to_json($this->_attributes);
      }

      function to_xml() {
         return to_xml(array(
            underscore(get_class($this)) => $this->attributes
         ));
      }

      # Automatic property accessors for model attributes

      function __get($key) {
         if (method_exists($this, $getter = "get_$key")) {
            return $this->$getter();
         } elseif (array_key_exists($key, $this->_attributes)) {
            return $this->read_attribute($key);
         } elseif (method_exists($this, $key)) {
            return $this->$key();
         } else {
            throw new UndefinedMethod($this, $getter);
         }
      }

      function __set($key, $value) {
         if (in_array($key, $this->_readonly)) {
            throw new ApplicationError("Can't change read-only attribute '$key'");
         } else {
            if (is_string($value) and !in_array($key, $this->_skip_trim)) {
               $value = trim($value);
            }

            if (method_exists($this, $setter = "set_$key")) {
               $this->$setter(&$value);
            } elseif (array_key_exists($key, $this->_attributes)) {
               $this->write_attribute($key, $value);
            } else {
               throw new UndefinedMethod($this, $setter);
            }
         }

         return $this;
      }

      function get_mapper() {
         throw new NotImplemented("Model '".get_class($this)."'doesn't have a mapper");
      }

      function get_exists() {
         return !$this->_new_record;
      }

      function get_new_record() {
         return $this->_new_record;
      }

      function get_attributes() {
         return $this->_attributes;
      }

      function changed($key=null) {
         if ($key) {
            return array_key_exists($key, $this->_changed_attributes);
         } else {
            return !empty($this->_changed_attributes);
         }
      }

      function changes() {
         $changes = array();
         foreach ($this->_changed_attributes as $key => $old) {
            if (($new = $this->$key) != $old) {
               $changes[$key] = array($old, $new);
            }
         }

         return $changes;
      }

      # Create or update the model, calls the model mapper for the
      # actual implementation.
      function save($force_update=false) {
         if (!$this->is_valid()) {
            return false;
         }

         $action = ($this->_new_record ? 'create' : 'update');

         $this->call_filter("before_$action");
         $this->call_filter("before_save");

         # Get the changed values
         $attributes = array_get(
            $this->_attributes, array_keys($this->_changed_attributes)
         );
         array_delete($attributes, $this->_virtual_attributes);

         if ($action == 'create') {
            $result = $this->mapper->insert($attributes);
         } elseif (empty($attributes) and !$force_update) {
            return $this;
         } else {
            $result = $this->mapper->update($this, $attributes, $force_update);
         }

         if (is_array($result)) {
            $this->load($result);
         }

         $this->_new_record = false;
         $this->call_filter("after_save");
         $this->call_filter("after_$action");

         $this->_changed_attributes = array();

         return $this;
      }

      function destroy() {
         if ($this->_new_record) {
            return false;
         } else {
            $this->call_filter('before_destroy');

            $this->mapper->delete($this);

            $this->_new_record = true;
            $this->freeze();

            $this->call_filter('after_destroy');

            return true;
         }
      }

      function validate() {}

      # Wrappers for use in custom setters and getters

      function read_attribute($key) {
         return $this->_attributes[$key];
      }

      function write_attribute($key, $value) {
         $old_value = $this->read_attribute($key);
         $this->_attributes[$key] = &$value;

         if ($this->read_attribute($key) != $old_value) {
            $this->_changed_attributes[$key] = $old_value;
         }
         return $this;
      }

      function add_virtual($key, $value) {
         if (array_key_exists($key, $this->_attributes)) {
            throw new ValueError($key, "Attribute '$key' already exists");
         }

         $this->_virtual_attributes[] = $key;
         return $this->_attributes[$key] = $value;
      }

      function reset($key) {
         if (!is_null($old = $this->_changed_attributes[$key])) {
            unset($this->_changed_attributes[$key]);
            return $this->_attributes[$key] = $old;
         }

         return false;
      }

      function freeze() {
         $this->_readonly = array_keys($this->attributes);
      }

      # Merge the attributes, protected keys are skipped
      function set_attributes(array $attributes=null) {
         if (is_array($attributes) and !empty($attributes)) {
            array_delete($attributes, $this->_protected);
            foreach ($attributes as $key => $value) {
               if (is_numeric($key)) {
                  throw new TypeError($key);
               } else {
                  $this->__set($key, $value);
               }
            }

            return $this;
         } else {
            return false;
         }
      }

      # Load attributes directly, adding as virtual if they don't exist yet
      function load(array $attributes) {
         foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $this->_attributes)) {
               # Add unknown attributes as virtual
               $this->add_virtual($key, $value);
            } else {
               $this->_attributes[$key] = $value;
            }
         }

         $this->_new_record = false;
         $this->_changed_attributes = array();

         return $this;
      }

      # Reload attributes from the mapper
      function reload() {
         if (!$this->_new_record) {
            return $this->load($this->mapper->find($this)->attributes);
         } else {
            return false;
         }
      }

      # Set an attribute only if it isn't set yet
      function set_default($key, $value) {
         if ($this->read_attribute($key) === null) {
            return $this->write_attribute($key, $value);
         }
      }

      # Update attributes and save
      function update(array $attributes) {
         if ($this->set_attributes($attributes) and $this->is_valid()) {
            return $this->save();
         } else {
            return false;
         }
      }

      # Error handling and validation

      function add_error($key, $message=null) {
         if ($message) {
            $this->_errors[$key][] = $message;
         } else {
            $this->_errors['generic'][] = $message;
         }
      }

      function get_errors() {
         return $this->_errors;
      }

      function is_valid() {
         $this->call_filter('before_validation');

         $this->_errors = array();
         $this->validate();

         $this->call_filter('after_validation');
         return empty($this->_errors);
      }

      protected function validate_attribute($key, $message, $valid) {
         if ($valid) {
            return true;
         } else {
            if (!in_array($key, $this->_errors)) {
               $this->add_error($key, sprintf($message, humanize($key)));
            }
            return false;
         }
      }

      # Validation checks

      protected function is_present($key) {
         return $this->validate_attribute($key,
            _("%s can't be blank"),
            !blank($this->_attributes[$key])
         );
      }

      protected function is_numeric($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("%s is not numeric"),
            ($allow_empty and $value == '') or is_numeric($value) or is_bool($value)
         );
      }

      protected function is_alpha($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("%s can only contain letters"),
            ($allow_empty and $value == '') or ctype_alpha($value)
         );
      }

      protected function is_alnum($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("%s can only contain alphanumeric characters"),
            ($allow_empty and $value == '') or preg_match('/^[\w\.-]*$/', $value)
         );
      }

      protected function is_email($key, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("%s is not valid"),
            ($allow_empty and $value == '') or is_email($value)
         );
      }

      protected function is_confirmed($key) {
         return $this->validate_attribute("{$key}_confirmation",
            _("%s doesn't match"),
            $this->_attributes[$key] == $this->_attributes["{$key}_confirmation"]
         );
      }

      protected function in_array($key, $array, $allow_empty=false) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            _("%s is invalid"),
            ($allow_empty and $value == '') or in_array($value, $array)
         );
      }

      protected function has_length($key, $min=null, $max=null, $allow_empty=false) {
         $value = $this->_attributes[$key];
         $length = mb_strlen($value);

         if ($allow_empty and $value == '') {
            return true;
         }

         if (is_null($max)) {
            $message = sprintf(_("%%s must be at least %d characters"), $min);
            $valid = ($length >= $min);
         } elseif ($min <= 0) {
            $message = sprintf(_("%%s cannot be longer than %d characters"), $max);
            $valid = ($length <= $max);
         } else {
            $message = sprintf(_("%%s must be between %d and %d characters"), $min, $max);
            $valid = ($length >= $min and $length <= $max);
         }

         return $this->validate_attribute($key, $message , $valid);
      }

      protected function has_format($key, $format) {
         $length = count($this->_attributes[$key]);
         return $this->validate_attribute($key,
            _("%s is invalid"),
            preg_match($format, $this->_attributes[$key])
         );
      }

      # Wrappers for URL helpers

      function link_to($action='show', $title=null, $options=null, $url_options=null) {
         $this->add_url_options($options, $action);
         $path = $this->to_params($action);
         $title = any($title, truncate($this, 40, true));
         return link_to($title, $path, $options, $url_options);
      }

      function button_to($action='show', $title=null, $options=null, $url_options=null) {
         $this->add_url_options($options, $action);
         $path = $this->to_params($action);
         $title = any($title, truncate($this, 20, true));
         return button_to($title, $path, $options, $url_options);
      }

      protected function add_url_options(array &$options=null, $action) {
         if ($action == 'destroy') {
            $options = array_merge(array(
               'confirm' => true,
               'post'    => true,
            ), (array) $options);
         }
      }

      # Wrappers for form helpers

      function form_element($attribute, $tag, array $options=null) {
         $key = underscore(get_class($this))."[$attribute]";
         $value = any(array_delete($options, 'value'), $this->_attributes[$attribute]);

         $options['id'] = $this->get_dom_id($attribute);
         $options['force'] = true;
         if ($this->_errors[$attribute]) {
            $options['errors'] = true;
         }

         switch ($tag) {
            case 'check_box':
               return hidden_field($key, '0')
                    . check_box($key, '1', $value, $options);
            case 'select_tag':
               return select_tag($key, array_delete($options, 'values'), $value, $options);
            default:
               return $tag($key, $value, $options);
         }
      }

      # Generate automatic form fields based on a lucky guess
      function auto_field($key) {
         $args = func_get_args();

         if (!array_key_exists($key, $this->_attributes)) {
            throw new ValueError("Invalid attribute '$key'");
         } elseif (in_array($key, $this->_readonly)) {
            return h($this->$key);
         } elseif ($key == 'text') {
            $method = 'text_area';
         } elseif (substr($key, 0, 8) == 'password') {
            $method = 'password_field';
         } else {
            $method = 'text_field';
         }

         return call_user_func_array(array($this, $method), $args);
      }

      function label($key, $label=null, array $options=null) {
         return label($this->get_dom_id($key), any($label, humanize($key)));
      }

      function text_field($key, array $options=null) {
         return $this->form_element($key, 'text_field', $options);
      }

      function text_area($key, array $options=null) {
         return $this->form_element($key, 'text_area', $options);
      }

      function password_field($key, array $options=null) {
         return $this->form_element($key, 'password_field', $options);
      }

      function hidden_field($key, array $options=null) {
         return $this->form_element($key, 'hidden_field', $options);
      }

      function check_box($key, array $options=null) {
         return $this->form_element($key, 'check_box', $options);
      }

      function select_tag($key, $values, array $options=null) {
         $options['values'] = $values;
         return $this->form_element($key, 'select_tag', $options);
      }

      function date_field($key=null, array $options=null) {
         return $this->form_element($key, 'date_field', $options);
      }

      function get_dom_id($key=null) {
         if ($key) {
            return underscore(get_class($this)).($key ? "_$key" : '');
         } else {
            return underscore(get_class($this)).'-'.$this->id;
         }
      }
   }

?>
