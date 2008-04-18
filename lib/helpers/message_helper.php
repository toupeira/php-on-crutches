<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function messages($keys=null) {
      $keys = func_get_args();
      $messages = '';

      if (is_array($msg = Dispatcher::$controller->msg)) {
         foreach ($msg as $key => $message) {
            if (!empty($keys) and !in_array($key, $keys)) {
               continue;
            } else {
               if (is_array($message)) {
                  $message = (count($message) == 1 ? $message[0] : list_tag($message));
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
