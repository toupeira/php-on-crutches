<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   try_require('/usr/share/php/libphp-phpmailer/class.phpmailer.php');

   class Mail extends Object
   {
      protected $_view;
      protected $_mailer;

      function __construct($template=null, $layout=null, $data=null) {
         $this->_mailer = new PHPMailer();
         $this->set_language('en', '/usr/share/php/libphp-phpmailer/language/');
         $this->char_set = 'utf-8';
         $this->from = config('mail_from');
         $this->from_name = config('mail_from_name');
         $this->sender = any(config('mail_sender'), config('mail_from'));

         $this->_view = new View($template, $layout);
         foreach ((array) $data as $key => $value) {
            $this->_view->set($key, $value);
         }
      }

      function get_template() {
         return $this->_view->template;
      }

      function set_template($template) {
         return $this->_view->template = $template;
      }

      function get_layout() {
         return $this->_view->layout;
      }

      function set_layout($layout) {
         return $this->_view->layout = $layout;
      }

      function set($key, $value) {
         $this->_view->set($key, $value);
         return $this;
      }

      function send() {
         if ($this->body == '') {
            $this->body = $this->_view->render();
            if ($this->content_type == 'text/html') {
               $this->alt_body = strip_html($this->body);
            }
         }

         $recipients = array_pluck($this->_mailer->to, 0);
         log_info("Sending mail to '".implode("', '", $recipients)."'");

         if (!config('send_mails')) {
            $GLOBALS['_SENT_MAILS'][] = array(
               'from'      => $this->from,
               'from_name' => $this->from_name,
               'to'        => $recipients,
               'subject'   => $this->subject,
               'template'  => $this->template,
               'layout'    => $this->layout,
               'body'      => $this->body,
            );

            return true;
         }

         if ($this->_mailer->send()) {
            return true;
         } else {
            throw new MailerError($this->error_info);
         }
      }

      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } elseif (property_exists($this->_mailer, $key = camelize($key))) {
            return $this->_mailer->$key;
         } else {
            throw new UndefinedMethod($this->_mailer, $getter);
         }
      }

      function __set($key, $value) {
         $setter = "set_$key";
         if (method_exists($this, $setter)) {
            $this->$setter(&$value);
         } elseif (property_exists($this->_mailer, $key = camelize($key))) {
            $this->_mailer->$key = $value;
         } else {
            throw new UndefinedMethod($this->_mailer, $setter);
         }

         return $this;
      }

      function __call($method, $args) {
         if (method_exists($this->_mailer, $method = camelize($method))) {
            return call_user_func_array(array($this->_mailer, $method), $args);
         } else {
            throw new UndefinedMethod($this->_mailer, $method);
         }
      }
   }

?>
