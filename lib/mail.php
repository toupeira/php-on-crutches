<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   try_require('/usr/share/php/libphp-phpmailer/class.phpmailer.php');

   if (class_exists(PHPMailer)) {
      class PHPMailerWrapper extends PHPMailer
      {
         function GetRecipients() {
            return (array) $this->to;
         }
      }
   }

   class Mail extends View
   {
      protected $_mailer;

      function __construct($template=null, $layout=null, $data=null) {
         parent::__construct($template, $layout);
         foreach ((array) $data as $key => $value) {
            $this->set($key, $value);
         }

         $this->_mailer = new PHPMailerWrapper();
         $this->set_language('en', '/usr/share/php/libphp-phpmailer/language/');
         $this->char_set = 'utf-8';
         $this->from = config('mail_from');
         $this->from_name = config('mail_from_name');
         $this->sender = any(config('mail_sender'), config('mail_from'));
      }

      function __get($key) {
         if (method_exists($this, $getter = "get_$key")) {
            return $this->$getter();
         } elseif (method_exists($this, $key)) {
            return $this->$key();
         } elseif (property_exists($this->_mailer, $key = camelize($key))) {
            return $this->_mailer->$key;
         } else {
            throw new UndefinedMethod($this->_mailer, $getter);
         }
      }

      function __set($key, $value) {
         if (method_exists($this, $setter = "set_$key")) {
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

      function send() {
         if ($this->body == '') {
            $this->body = $this->render();
            if ($this->content_type == 'text/html') {
               $this->alt_body = strip_html($this->body);
            }
         }

         $recipients = array_pluck($this->get_recipients(), 0);
         log_info("Sending mail to '".implode("', '", $recipients)."'");

         if (!config('send_mails')) {
            $GLOBALS['_SENT_MAILS'][] = array(
               'from'      => $this->from,
               'from_name' => $this->from_name,
               'to'        => $recipients,
               'cc'        => array_pluck($this->_mailer->cc, 0),
               'bcc'       => array_pluck($this->_mailer->bcc, 0),
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
   }

?>
