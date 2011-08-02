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
      protected $_frozen = false;

      function __construct(array $attributes=null, array $defaults=null) {
         # Add virtual attributes
         array_map(array($this, 'add_virtual'), $this->_virtual_attributes);

         # Set default values
         $this->set_attributes(array_merge(
            (array) $defaults,
            (array) $attributes
         ));

         $this->call_if_defined('init');
      }

      function __destruct() {
         $this->dispose(0);
      }

      function __toString() {
         if ($keys = $this->_display_column) {
            foreach ((array) $keys as $key) {
               if (!blank($value = $this->$key)) {
                  return (string) $value;
               }
            }
         } else {
            foreach (array('title', 'name', 'username', 'filename', 'key', 'id') as $key) {
               $getter = any(
                  method_exists($this, "get_$key"),
                  array_key_exists($key, $this->_attributes)
               );

               if ($getter and !blank($value = $this->$key)) {
                  return (string) $value;
               }
            }
         }

         return parent::__toString();
      }

      function inspect() {
         return parent::inspect($this->attributes);
      }

      function serialize() {
         $this->_mapper = null;
         return serialize($this);
      }

      function to_param() {
         return $this->slug;
      }

      function to_params($action=null) {
         return array(
            'controller' => tableize(get_class($this)),
            'action'     => any($action, 'show'),
            'id'         => ($this->exists ? $this->to_param() : null),
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
         } elseif (method_exists($this, $generator = "generate_$key")) {
            return $this->add_virtual($key, $this->$generator());
         } elseif (method_exists($this, $cache = "cache_$key")) {
            $key = $this->get_cache_key($key);
            if (is_null($value = cache($key))) {
               $value = cache_set($key, $this->$cache());
            }
            return $this->add_virtual($key, $value);
         } elseif (method_exists($this, '__get_custom')) {
            return $this->__get_custom($key);
         } else {
            throw new UndefinedMethod($this, $getter);
         }
      }

      function __set($key, $value) {
         if ($this->_frozen) {
            throw new ApplicationError(sprintf("Can't change frozen %s instance", get_class($this)));
         } elseif (in_array($key, $this->_readonly)) {
            throw new ApplicationError(sprintf("Can't change read-only attribute '$key' in %s instance", get_class($this)));
         } elseif (method_exists($this, $setter = "set_$key")) {
            $this->$setter($value);
         } elseif (array_key_exists($key, $this->_attributes)) {
            $this->write_attribute($key, $value);
         } else {
            throw new UndefinedMethod($this, $setter);
         }
      }

      function get_mapper() {
         if (is_null($this->_mapper)) {
            $mapper = get_class($this).'Mapper';
            $this->_mapper = new $mapper();
         }

         return $this->_mapper;
      }

      function get_exists() {
         return !$this->_new_record;
      }

      function get_new_record() {
         return $this->_new_record;
      }

      function get_frozen() {
         return $this->_frozen;
      }

      function get_attributes() {
         return $this->_attributes;
      }

      function get_virtual_attributes() {
         return $this->_virtual_attributes;
      }

      function get_readonly() {
         return $this->_readonly;
      }

      function get_protected() {
         return $this->_protected;
      }

      function changed($key=null) {
         if ($key) {
            foreach (func_get_args() as $key) {
               if (array_key_exists($key, $this->_changed_attributes)) {
                  return true;
               }
            }
            return false;
         } else {
            return !empty($this->_changed_attributes);
         }
      }

      function changes() {
         $changes = array();
         foreach ($this->_changed_attributes as $key => $old) {
            if (($new = $this->$key) !== $old) {
               $changes[$key] = array($old, $new);
            }
         }

         return $changes;
      }

      # Create or update the model, calls the model mapper for the
      # actual implementation.
      function save($force_update=false, $skip_validation=false) {
         if ($this->_frozen) {
            throw new ApplicationError("Can't change frozen object");
         } elseif (!$skip_validation and !$this->is_valid()) {
            return false;
         }

         $action = ($this->_new_record ? 'create' : 'update');

         $this->call_filter("before_$action", $action);
         $this->call_filter("before_save");

         if (!$skip_validation and !empty($this->_errors)) {
            return false;
         }

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
            $this->load($result, false);
         }

         $this->_new_record = false;

         $this->call_filter("after_save", $action);
         $this->call_filter("after_$action");

         $this->_changed_attributes = array();

         return $this;
      }

      function destroy() {
         if ($this->_frozen) {
            throw new ApplicationError("Can't change frozen object");
         } elseif ($this->_new_record) {
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
         if ($this->_frozen) {
            throw new ApplicationError("Can't change frozen object");
         }

         $old_value = $this->_attributes[$key];

         if (is_string($value) and $this->_skip_trim !== true and !in_array($key, $this->_skip_trim)) {
            $value = trim($value);
         }

         if (!is_null($value) or $old_value) {
            $this->_attributes[$key] = &$value;

            if ($old_value !== $value) {
               $this->_changed_attributes[$key] = $old_value;
            }
         }

         return $value;
      }

      function add_virtual($key, $value=null) {
         $exists = array_key_exists($key, $this->_attributes);

         if (!in_array($key, $this->_virtual_attributes)) {
            if ($exists) {
               throw new ValueError($key, "Attribute '$key' already exists");
            } else {
               $this->_virtual_attributes[] = $key;
            }
         }

         if (func_num_args() > 1 or !$exists) {
            return $this->_attributes[$key] = $value;
         } else {
            return $this->_attributes[$key];
         }
      }

      function reset($key=null) {
         if ($this->_frozen) {
            throw new ApplicationError("Can't change frozen object");
         } elseif (is_null($key)) {
            foreach ($this->_changed_attributes as $key => $old) {
               $this->_attributes[$key] = $old;
            }
            $this->_changed_attributes = array();

            return true;
         } else {
            foreach (func_get_args() as $key) {
               if (!is_null($old = $this->_changed_attributes[$key])) {
                  unset($this->_changed_attributes[$key]);
                  $this->_attributes[$key] = $old;
               }
            }

            return true;
         }

         return false;
      }

      function dispose($recurse=1, $parent=null) {
         if ($recurse) {
            foreach ($this->_attributes as $value) {
               if ($value instanceof Model and $value != $parent) {
                  $value->dispose(max(0, $recurse - 1), any($parent, $this));
               }
            }
         }

         $this->_mapper = null;
         $this->_attributes = array();
         $this->_changed_attributes = array();
         $this->_errors = array();
         $this->_new_record = true;

         return $this->freeze();
      }

      function freeze() {
         $this->_frozen = true;
         return $this;
      }

      function thaw() {
         $this->_frozen = false;
         return $this;
      }

      # Merge the attributes, protected keys are skipped
      function set_attributes(array $attributes=null) {
         if (is_array($attributes) and !empty($attributes)) {
            array_delete($attributes, $this->_protected);
            foreach ($attributes as $key => $value) {
               if (is_numeric($key)) {
                  throw new TypeError($key);
               } elseif (!array_key_exists($key, $this->_attributes)) {
                  $this->add_virtual($key, $value);
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
      function load(array $attributes, $reset=true) {
         if ($this->_frozen) {
            throw new ApplicationError("Can't change frozen object");
         }

         foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $this->_attributes)) {
               # Add unknown attributes as virtual
               $this->add_virtual($key, $value);
            } else {
               $this->_attributes[$key] = $value;
            }
         }

         if ($reset) {
            $this->_new_record = false;
            $this->_changed_attributes = array();
         }

         return $this;
      }

      # Reload attributes from the mapper
      function reload() {
         if ($this->_frozen) {
            throw new ApplicationError("Can't change frozen object");
         } elseif (!$this->_new_record) {
            if ($data = $this->mapper->find($this)->attributes) {
               return $this->load($data);
            } else {
               $this->_new_record = true;
               throw new ApplicationError("Object is gone");
            }
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
      function update(array $attributes=null) {
         $this->set_attributes($attributes);
         return $this->save();
      }

      # Error handling and validation

      function add_error($key, $message=null) {
         if ($message) {
            $this->_errors[$key][] = $message;
         } else {
            $this->_errors['generic'][] = $key;
         }
      }

      function get_errors() {
         return $this->_errors;
      }

      function get_first_error() {
         if ($errors = $this->_errors) {
            return array_shift(array_shift($errors));
         }
      }

      function clear_errors() {
         $this->_errors = array();
         return true;
      }

      function is_valid() {
         $this->call_filter('before_validation');

         $this->_errors = array();
         $this->validate();

         $this->call_filter('after_validation');
         return empty($this->_errors);
      }

      protected function validate_attribute($keys, $valid, $message=null, $default_message=null) {
         if ($valid) {
            return true;
         } else {
            $keys = (array) $keys;
            $message = sprintf(
               any($message, $default_message, _("%s is invalid")),
               humanize($keys[0])
            );

            foreach ($keys as $key) {
               if (!array_key_exists($key, $this->_attributes)) {
                  throw new ValueError($key, "Invalid attribute '%s'");
               } elseif (!array_key_exists($key, $this->_errors)) {
                  $this->add_error($key, $message);
               }
            }

            return false;
         }
      }

      # Validation checks

      protected function is_present($key, $message=null) {
         return $this->validate_attribute($key,
            !blank($this->_attributes[$key]),
            $message, _("%s can't be blank")
         );
      }

      protected function is_numeric($key, $allow_empty=false, $message=null) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            ($allow_empty and $value == '') or is_numeric($value) or is_bool($value),
            $message, _("%s is not numeric")
         );
      }

      protected function is_alpha($key, $allow_empty=false, $message=null) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            ($allow_empty and $value == '') or ctype_alpha($value),
            $message, _("%s can only contain letters")
         );
      }

      protected function is_alnum($key, $allow_empty=false, $message=null) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            ($allow_empty and $value == '') or preg_match('/^[\w\.-]*$/', $value),
            $message, _("%s can only contain alphanumeric characters")
         );
      }

      protected function is_email($key, $allow_empty=false, $message=null) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            ($allow_empty and $value == '') or is_email($value),
            $message
         );
      }

      protected function is_reachable_email($key, $allow_empty=false, $message=null) {
         $value = $this->_attributes[$key];
         $domain = array_pop(explode('@', $value, 2));

         return $this->validate_attribute($key,
            ($allow_empty and $value == '') or is_reachable_email($value),
            sprintf(_("%s is not a valid email domain"), h($domain))
         );
      }

      protected function is_confirmed($key, $message=null) {
         return $this->validate_attribute("{$key}_confirmation",
            $this->_attributes[$key] == $this->_attributes["{$key}_confirmation"],
            $message, _("%s doesn't match")
         );
      }

      protected function in_array($key, $array, $allow_empty=false, $message=null) {
         $value = $this->_attributes[$key];
         return $this->validate_attribute($key,
            ($allow_empty and $value == '') or in_array($value, $array),
            $message
         );
      }

      protected function has_length($key, $min=null, $max=null, $allow_empty=false) {
         $value = $this->_attributes[$key];
         $length = mb_strlen($value);

         if ($allow_empty and $value == '') {
            return true;
         }

         if (is_null($max)) {
            $default = sprintf(_("%%s must be at least %d characters"), $min);
            $valid = ($length >= $min);
         } elseif ($min <= 0) {
            $default = sprintf(_("%%s cannot be longer than %d characters"), $max);
            $valid = ($length <= $max);
         } else {
            $default = sprintf(_("%%s must be between %d and %d characters"), $min, $max);
            $valid = ($length >= $min and $length <= $max);
         }

         return $this->validate_attribute($key, $valid, $message, $default);
      }

      protected function has_format($key, $format, $message=null) {
         $length = count($this->_attributes[$key]);
         return $this->validate_attribute($key,
            preg_match($format, $this->_attributes[$key]),
            $message
         );
      }

      # Wrappers for URL helpers

      function link_to($action=null, $title=null, array $options=null, array $url_options=null) {
         $this->add_url_options($options, $action);
         $action = any($action, 'show');

         if (is_null($title)) {
            $title = truncate($this, 40, true);
         }

         $path = $this->to_params($action);
         return link_to($title, $path, $options, $url_options);
      }

      function icon_link_to($action=null, $title=null, $icon=null, array $options=null, array $url_options=null) {
         $action = any($action, 'show');

         if (!$icon) {
            $icon = underscore(get_class($this));
            if ($action != 'show' and $action != 'index') {
               $icon .= "_$action";
            }
         }

         if (!$icon_title = $options['icon_title']) {
            $icon_title = strip_html($title);
         }

         unset($options['icon_title']);

         $title = icon($icon, array('title' => $icon_title))
                . any($title, truncate($this, 40, true));

         return $this->link_to($action, $title, $options, $url_options);
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

         $size = $this->mapper->attributes[$attribute]['size'];
         if ($size > 0 and !isset($options['maxlength']) and ($tag == 'text_field' or $tag == 'text_area')) {
            $options['maxlength'] = $size;
         }

         if (isset($options['value'])) {
            $value = $checkbox = $options['value'];
            unset($options['value']);
         } else {
            $value = $this->_attributes[$attribute];
            $options['force'] = true;
         }

         if (!array_key_exists('id', (array) $options)) {
            $options['id'] = $this->get_dom_id($attribute);
         }

         if ($this->_errors[$attribute]) {
            $options['errors'] = true;
         }

         switch ($tag) {
            case 'check_box':
               return hidden_field($key, '0', array('id' => null))
                    . check_box($key, isset($checkbox) ? $checkbox : 1, $value and $value == $this->_attributes[$attribute], $options);
            case 'radio_button':
               return radio_button($key, $value, $value == $this->_attributes[$attribute], $options);
            case 'select_tag':
               return select_tag($key, array_delete($options, 'values'), any(array_delete($options, 'selected'), $value), $options);
            default:
               return $tag($key, $value, $options);
         }
      }

      # Generate automatic form fields based on a lucky guess
      function auto_field($key) {
         $args = func_get_args();

         if (!array_key_exists($key, $this->_attributes)) {
            throw new ValueError("Invalid attribute '$key'");
         } elseif ($this->_frozen or in_array($key, $this->_readonly)) {
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

      function radio_button($key, $value, array $options=null) {
         $options['value'] = $value;
         return $this->form_element($key, 'radio_button', $options);
      }

      function select_tag($key, $values, array $options=null) {
         $options['values'] = $values;
         return $this->form_element($key, 'select_tag', $options);
      }

      function date_field($key=null, array $options=null) {
         return $this->form_element($key, 'date_field', $options);
      }

      function expire_attribute($attribute=null) {
         foreach (func_get_args() as $attribute) {
            cache_expire($this->get_cache_key($attribute));
         }

         return true;
      }

      function get_cache_key($attribute=null) {
         $key = underscore(get_class($this)).'-'.intval($this->id);
         if ($attribute) {
            $key .= '-'.$attribute;
         }

         return $key;
      }

      function get_dom_id($attribute=null) {
         return $this->dom_class.($attribute ? "_$attribute" : '-'.intval($this->id));
      }

      function get_dom_class() {
         return underscore(get_class($this));
      }
   }

?>
