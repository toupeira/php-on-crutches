<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function add_message($key, $message=null) {
      if (!$message) {
         $message = $key;
         $key = 'info';
      }

      if (Dispatcher::$controller) {
         Dispatcher::$controller->msg[$key][] = $message;
      }
   }

   function messages($keys=null) {
      if (!Dispatcher::$controller) {
         return false;
      }

      $keys = func_get_args();
      $messages = '';

      if (is_array($msg = Dispatcher::$controller->msg)) {
         foreach ($msg as $key => $message) {
            if (!empty($keys) and !in_array($key, $keys)) {
               continue;
            } else {
               if (is_array($message)) {
                  if (count($message) == 1) {
                     $message = h($message[0]);
                  } else {
                     $message = list_tag($message, array('escape' => true));
                  }
               } else {
                  $message = h($message);
               }

               $message = preg_replace('/\[\[([^]]+)\]\]/', '<code>$1</code>', $message);
               $messages .= content_tag('div', $message, array(
                  'class' => "message $key"
               ));
            }
         }
      }

      return $messages;
   }

?>
