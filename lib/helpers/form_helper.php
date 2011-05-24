<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   # Build a form for the current path, or the given action.
   # Use `$options['multipart'] = true` for upload forms.
   function form_tag($action=null, array $options=null) {
      if ($action instanceof Model) {
         $action = $action->to_params($action->new_record ? 'create' : 'edit');
      }

      $options = array_merge(array(
         'action' => url_for(any($action, ltrim(Dispatcher::$path, '/'))),
         'method' => 'post', 'open' => true,
      ), (array) $options);

      if (array_delete($options, 'multipart')) {
         $options['enctype'] = 'multipart/form-data';
      }

      if ($options['method']) {
         $options['method'] = strtolower($options['method']);
      }

      $form = content_tag('form', null, $options).N;

      # Add a token to POST forms
      if ($options['method'] == 'post' and $token = form_token()) {
         $form .= hidden_field('_form_token', $token, array('id' => null, 'force' => true)).N;
      }

      return $form;
   }

   function form_end() {
      return "</form>\n";
   }

   # Generate a unique form token for this session
   function form_token() {
      static $_token;

      if (!$_token and config('form_token') and $controller = Dispatcher::$controller and $id = session_id()) {
         if (!$key = config('secret_key')) {
            throw new ConfigurationError("No secret key set");
         }

         $_token = sha1($id.$key);

         $controller->session['form_token'] = $_token;
         if (config('form_token_time')) {
            $controller->session['form_token_time'] = time();
         }
      }

      return $_token;
   }

   function form_element_id($key) {
      return preg_replace(
         '/\[([^\]]+)\]/', '_\1',
         str_replace('[]', '', $key)
      );
   }

   function form_element($tag, $key, $default_value=null, array $options=null, array $defaults=null) {
      # Merge tag options
      $options = array_merge(
         array('name' => $key),
         (array) $defaults,
         (array) $options
      );

      # Set a default id
      if (!array_key_exists('id', (array) $options)) {
         $options['id'] = form_element_id($key);
      }

      # Use request value if set, else use default value
      if (!$options['force'] and $request_value = form_element_value($key)) {
         $value = $request_value;
      } else {
         $value = $default_value;
      }

      unset($options['force']);

      # Check if an error is set for this field
      if ($options['type'] !== 'checkbox' and $options['type'] !== 'radio' and
         ($options['errors'] or (Dispatcher::$controller and Dispatcher::$controller->has_errors($key)))) {
         $options['class'] .= ' error';

         if ($onchange = $options['onchange']) {
            $onchange .= '; ';
         }

         $options['onchange'] = "$onchange$(this).removeClassName('error')";
      }

      unset($options['errors']);

      # Build the actual tag
      if ($tag == 'input' and in_array($options['type'], array('checkbox', 'radio'))) {
         $options['value'] = $default_value;
         if ($request_value) {
            $options['checked'] = ($value == $default_value);
         } elseif (substr($key, -2) == '[]' and is_array($values = $_REQUEST[substr($key, 0, -2)])) {
            $options['checked'] = in_array($default_value, (array) $values);
         }
      } elseif ($tag == 'input') {
         $options['value'] = $value;
      } elseif ($tag == 'textarea') {
         return content_tag($tag, $value, $options);
      } elseif ($tag == 'select') {
         return content_tag($tag, $default_value, $options);
      }

      return tag($tag, $options);
   }

   function form_element_value($key) {
      $params = &Dispatcher::$params;

      if (preg_match('/^(\w+)((?:\[\w+\])+)$/', $key, $match)) {
         # Get value from nested key
         list($m, $object, $keys) = $match;
         if (isset($params[$object])) {
            $value = &$params[$object];

            foreach (explode('][', trim($keys, '][')) as $key) {
               if (isset($value[$key])) {
                  $value = &$value[$key];
               } else {
                  return;
               }
            }
         }
      } elseif (isset($params[$key])) {
         $value = $params[$key];
      }

      return $value;
   }

   # Add necessary options for confirmation, used in buttons and link_to()
   function add_confirm_options(array &$options=null) {
      $confirm = $options['confirm'];
      unset($options['confirm']);

      if ($confirm) {
         $message = ($confirm === true ? _("Are you sure?") : $confirm);
         $options['onclick'] = "return confirm('$message')";
         return $message;
      }
   }

   function label($key, $label=null, array $options=null) {
      return content_tag('label', any($label, humanize($key)), array_merge(
         (array) $options, array('for' => form_element_id($key))
      ));
   }

   function text_field($key, $value=null, array $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'text', 'size' => 20
      ));
   }

   function text_area($key, $value=null, array $options=null) {
      return form_element('textarea', $key, $value, $options, array(
         'cols' => 50, 'rows' => 8
      ));
   }

   function password_field($key, $value=null, array $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'password', 'size' => 20
      ));
   }

   function file_field($key, array $options=null) {
      return form_element('input', $key, null, $options, array(
         'type' => 'file', 'size' => 30
      ));
   }

   function hidden_field($key, $value, array $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'hidden'
      ));
   }

   function check_box($key, $value='1', $checked=null, array $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'checkbox', 'checked' => (bool) $checked
      ));
   }

   function radio_button($key, $value, $checked=null, array $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'radio', 'checked' => (bool) $checked
      ));
   }

   function select_tag($key, array $values, $selected=null, array $options=null) {
      if (!$options['force'] and $request_value = form_element_value($key)) {
         $selected = $request_value;
      }

      # Build option tags
      $items = '';
      $values_only = array_delete($options, 'values_only');

      foreach ((array) $values as $id => $value) {
         $items .= content_tag('option', h($value), array(
            'value'    => ($values_only ? null : $id),
            'selected' => (in_array(($values_only ? $value : $id), (array) $selected) ? 'selected' : null),
         ));
      }

      $options['force'] = true;
      return form_element('select', $key, $items, $options);
   }

   function date_field($key, $value, array $options) {
      if (!is_numeric($value)) {
         $value = strtotime($value);
      }

      list($year, $month, $day) = explode('-', strftime('%Y-%m-%d', $value), 3);

      $years = any(array_delete($options, 'years'), range($year + 25, $year - 100));
      $months = any(array_delete($options, 'months'), range(1, 12));
      $days = any(array_delete($options, 'days'), range(1, 31));

      $options['values_only'] = true;

      return select_tag("{$key}[year]", $years, $year, $options) . ' '
           . select_tag("{$key}[month]", $months, $month, $options) . ' '
           . select_tag("{$key}[day]", $days, $day, $options);
   }

   function submit_button($title=null, array $options=null) {
      add_confirm_options($options);

      if (array_delete($options, 'block')) {
         $options['onclick'] = 'this.onclick = function() { return false; }';
      }

      return tag('input', $options, array(
         'type' => 'submit', 'value' => any($title, _('Save'))
      ));
   }

   function button_tag($title, array $options=null) {
      add_confirm_options($options);

      return tag('input', $options, array(
         'type' => 'button', 'value' => $title
      ));
   }

   function cancel_button($path=null, $title=null, array $options=null) {
      $options['class'] = $options['class'].' cancel button';
      $options['force_class'] = true;
      $options['onclick'] = is_null($path)
         ? "history.back(); return false"
         : "location.href = '".url_for($path)."'; return false";

      return button_tag(any($title, _("Cancel")), $options);
   }

   function timezone_list() {
      $codes = array();
      $locations = array();

      foreach (timezone_identifiers_list() as $zone) {
         if ($zone == 'localtime' or substr($zone, 0, 3) == 'GMT' or substr($zone, 0, 8) == 'SystemV') {
            continue;
         } elseif (preg_match('#^(Etc/|SystemV/)?([^/]+)$#', $zone, $match)) {
            $codes[$zone] = $match[2];
         } else {
            $locations[$zone] = $zone;
         }
      }

      return array_merge(sorted($locations), sorted($codes));
   }

?>
