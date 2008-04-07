<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function form_tag($action=null, $options=null) {
      $defaults = array(
         'action' => url_for(any($action, Dispatcher::$path)),
         'method' => 'post', 'open' => true,
      );

      if (array_delete($options, 'multipart')) {
         $defaults['enctype'] = 'multipart/form-data';
      }

      return content_tag('form', null, $options, $defaults).N;
   }

   function form_element($tag, $key, $default_value=null, $options=null, $defaults=null) {
      # Merge tag options
      $options = array_merge(
         array('name' => $key),
         (array) $defaults,
         (array) $options
      );

      # Get POST value
      if (preg_match('/(\w+)\[(\w+)\]/', $key, $match)) {
         list($m, $object, $key) = $match;
         if (isset($_POST[$object][$key])) {
            $post_value = $_POST[$object][$key];
         }
      } elseif (isset($_POST[$key])) {
         $post_value = $_POST[$key];
      }

      # Use POST value if set, else use default value
      if ($post_value === null) {
         $value = $default_value;
      } else {
         $value = $post_value;
      }

      # Check if an error is set for this field
      if (array_delete($options, 'errors')
          or (Dispatcher::$controller->has_errors($key)
              and !in_array($options['type'], array('checkbox', 'radio')))
      ) {
         $options['class'] .= ' error';
         $options['onkeypress'] = $options['onchange'] = '$(this).removeClassName(\'error\')';
      }

      # Build the actual tag
      if ($tag == 'input' and in_array($options['type'], array('checkbox', 'radio'))) {
         $options['value'] = $default_value;
         if ($post_value) {
            $options['checked'] = ($value == $default_value ? 'checked' : null);
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

   function text_field($key, $value=null, $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'text', 'size' => 20
      ));
   }

   function text_area($key, $value=null, $options=null) {
      return form_element('textarea', $key, $value, $options, array(
         'cols' => 40, 'rows' => 5
      ));
   }

   function password_field($key, $value=null, $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'password', 'size' => 20
      ));
   }

   function file_field($key, $options=null) {
      return form_element('input', $key, null, $options, array(
         'type' => 'file'
      ));
   }

   function check_box($key, $value='1', $checked=null, $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'checkbox', 'checked' => $checked ? 'checked' : null
      ));
   }

   function radio_button($key, $value, $checked=null, $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'radio', 'checked' => $checked ? 'checked' : null
      ));
   }

   function hidden_field($key, $value, $options=null) {
      return form_element('input', $key, $value, $options, array(
         'type' => 'hidden'
      ));
   }

   function submit_button($value, $options=null) {
      return tag('input', $options, array(
         'type' => 'submit', 'value' => $value
      ));
   }

   function select_tag($key, $values, $selected=null, $options=null) {
      if (isset($_POST[$key])) {
         $selected = $_POST[$key];
      }

      # Build option tags
      $items = '';
      foreach ((array) $values as $value => $text) {
         $items .= content_tag('option', h($text), array(
            'value' => $value, 'selected' => (
               in_array($value, (array) $selected) ? 'selected' : null
            )
         ));
      }

      return form_element('select', $key, $items, $options);
   }

?>
