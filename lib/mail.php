<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   safe_require('/usr/share/php/libphp-phpmailer/class.phpmailer.php');

   class Mail extends Object
   {
      protected $view;
      protected $mailer;

      function __construct($template=null, $layout=null, $data=null) {
         $this->mailer = new PHPMailer();
         $this->set_language('en', '/usr/share/php/libphp-phpmailer/language/');
         $this->char_set = 'utf-8';
         $this->from = config('mail_from');
         $this->from_name = config('mail_from_name');
         $this->sender = any(config('mail_sender'), config('mail_from'));

         $this->view = new View($template, $layout);
         foreach ((array) $data as $key => $value) {
            $this->view->set($key, $value);
         }
      }

      function get_template() {
         return $this->view->template;
      }

      function set_template($template) {
         return $this->view->template = $template;
      }

      function get_layout() {
         return $this->view->layout;
      }

      function set_layout($layout) {
         return $this->view->layout = $layout;
      }

      function set($key, $value) {
         $this->view->set($key, $value);
         return $this;
      }

      function send() {
         if ($this->body == '') {
            $this->body = $this->view->render();
            if ($this->content_type == 'text/html') {
               $this->alt_body = strip_html($body);
            }
         }

         $recipients = array_pluck($this->mailer->to, 0);
         log_info("Sending mail to '".implode("', '", $recipients)."'");

         # Don't send out mails when testing, store them in $_SENT_MAILS instead
         if (defined('TESTING')) {
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

         if ($this->mailer->send()) {
            return true;
         } else {
            throw new MailerError($this->error_info);
         }
      }

      function __get($key) {
         $getter = "get_$key";
         if (method_exists($this, $getter)) {
            return $this->$getter();
         } else {
            $key = camelize($key);
            return $this->mailer->$key;
         }
      }

      function __set($key, $value) {
         $setter = "set_$key";
         if (method_exists($this, $setter)) {
            $this->$setter(&$value);
         } else {
            $key = camelize($key);
            $this->mailer->$key = $value;
         }

         return $this;
      }

      function __call($method, $args) {
         return call_user_func_array(array($this->mailer, camelize($method)), $args);
      }
   }

?>