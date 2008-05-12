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
      $defaults = array(
         'action' => url_for(any($action, Dispatcher::$path)),
         'method' => 'POST', 'open' => true,
      );

      if (array_delete($options, 'multipart')) {
         $defaults['enctype'] = 'multipart/form-data';
      }

      if ($options['method']) {
         $options['method'] = strtoupper($options['method']);
      }

      return content_tag('form', null, $options, $defaults).N;
   }

   function form_end() {
      return "</form>\n";
   }

   function form_element($tag, $key, $default_value=null, array $options=null, array $defaults=null) {
      # Merge tag options
      $options = array_merge(
         array('name' => $key),
         array('id'   => $key),
         (array) $defaults,
         (array) $options
      );

      # Use request value if set, else use default value
      if (!array_delete($options, 'force') and $request_value = form_element_value($key)) {
         $value = $request_value;
      } else {
         $value = $default_value;
      }

      # Check if an error is set for this field
      if (array_delete($options, 'errors')
          or (Dispatcher::$controller->has_errors($key)
              and !in_array($options['type'], array('checkbox', 'radio')))
      ) {
         $options['class'] .= ' error';
         $options['onchange'] = "$(this).removeClassName('error')";
      }

      # Build the actual tag
      if ($tag == 'input' and in_array($options['type'], array('checkbox', 'radio'))) {
         $options['value'] = $default_value;
         if ($request_value) {
            $options['checked'] = ($value == $default_value);
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
      if (preg_match('/^(\w+)((?:\[\w+\])+)$/', $key, $match)) {
         # Get value from nested key
         list($m, $object, $keys) = $match;
         $value = &$_REQUEST[$object];
         foreach (explode('][', trim($keys, '][')) as $key) {
            $value = &$value[$key];
         }
      } elseif (isset($_REQUEST[$key])) {
         $value = $_REQUEST[$key];
      }

      return $value;
   }

   function label($key, $label=null, array $options=null) {
      return content_tag('label', _(any($label, humanize($key))), array_merge(
         (array) $options, array('for' => $key,)
      ));
   }

   function text_field($key, $value=null, array $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'text', 'size' => 20
      ));
   }

   function text_area($key, $value=null, array $options=null) {
      return form_element('textarea', $key, $value, $options, array(
         'cols' => 40, 'rows' => 5
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
      list($year, $month, $day) = explode('-', strftime('%Y-%m-%d'), 3);

      $years = any(array_delete($options, 'years'), range($year + 25, $year - 100));
      $months = any(array_delete($options, 'months'), range(1, 12));
      $days = any(array_delete($options, 'days'), range(1, 31));

      $options['values_only'] = true;

      return select_tag("{$key}[year]", $years, $year, $options) . ' '
           . select_tag("{$key}[month]", $months, $month, $options) . ' '
           . select_tag("{$key}[day]", $days, $day, $options);
   }

   function submit_button($title=null, array $options=null) {
      return tag('input', $options, array(
         'type' => 'submit', 'value' => any($title, _('Save'))
      ));
   }

   function cancel_button($path=null, $title=null, array $options=null) {
      return button_to(any($title, _('Cancel')), any($path, ':'), $options);
   }

?>
