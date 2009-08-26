<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   try_require('/usr/share/php/libphp-phpmailer/class.phpmailer.php');

   class Mail extends View
   {
      protected $_mailer;
      protected $_to;
      protected $_cc;
      protected $_bcc;

      function __construct($template=null, $data=null) {
         $this->template = $template;

         foreach ((array) $data as $key => $value) {
            $this->set($key, $value);
         }

         $this->_mailer = new PHPMailer();
         $this->set_language('en', '/usr/share/php/libphp-phpmailer/language/');
         $this->char_set = 'utf-8';
         $this->from = config('mail_from');
         $this->from_name = config('mail_from_name');
         $this->sender = any(config('mail_sender'), config('mail_from'));
      }

      function set_template($template) {
         return $this->_template = array($template, "emails/$template");
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

      function add_address($address, $name=null) {
         $this->_to[] = $address;
         return $this->_mailer->AddAddress($address, $name);
      }

      function add_cc($address, $name=null) {
         $this->_cc[] = $address;
         return $this->_mailer->AddCC($address, $name);
      }

      function add_bcc($address, $name=null) {
         $this->_bcc[] = $address;
         return $this->_mailer->AddBCC($address, $name);
      }

      function send() {
         foreach (func_get_args() as $address) {
            $this->add_address($address);
         }

         if ($this->body == '') {
            $this->body = $this->render();
            if ($this->content_type == 'text/html') {
               $this->alt_body = trim(strip_html($this->body));
            }
         }

         log_info("Sending mail to '".implode("', '", $this->_to)."'");

         if (config('send_mails')) {
            if ($this->_mailer->send()) {
               return true;
            } else {
               throw new MailerError($this->error_info);
            }
         } else {
            $GLOBALS['_SENT_MAILS'][] = array(
               'from'      => $this->from,
               'from_name' => $this->from_name,
               'to'        => $this->_to,
               'cc'        => $this->_cc,
               'bcc'       => $this->_bcc,
               'subject'   => $this->subject,
               'template'  => $this->template,
               'layout'    => $this->layout,
               'body'      => $this->body,
            );

            return true;
         }
      }
   }

?>
