<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class AuthenticatedController extends Controller
   {
      protected $_require_login;
      protected $_require_admin;

      static function is_logged_in() {
         $user = self::auth('current');
         if ($user instanceof AuthenticationModel) {
            return $user;
         } else {
            return false;
         }
      }

      static function is_admin() {
         if ($user = is_logged_in() and $user->admin and !$user->changed) {
            return $user;
         } else {
            return false;
         }
      }

      # Helper to access the configured authentication model
      static protected function auth($method) {
         $args = array_slice(func_get_args(), 1);
         return call_user_func_array(
            array(config('auth_model'), $method), $args
         );
      }

      # Check if the current request meets all authentication requirements
      function is_valid_request($action) {
         if (!parent::is_valid_request($action)) {
            return false;
         }

         if (!classify(config('auth_model'))) {
            throw new ConfigurationError("Missing or invalid authentication model");
         }

         $redirect = null;

         if ($id = $this->session['auth_id']) {
            # Login user from session
            if (!self::auth('login', $id)) {
               unset($this->session['auth_id']);
            }
         } elseif ($token = $this->cookies['auth_token']) {
            # Login user from cookie
            if ($user = self::auth('login', self::auth('authenticate_token', $token))) {
               log_info("Logged in user from cookie");
               $this->session['auth_id'] = self::auth('current')->id;
               $this->send_auth_token();
            }
         }

         # Check if the action requires a logged in user
         if ($this->check_requirement($action, 'login') and !self::is_logged_in()) {
            if ($this->request['method'] == 'GET' and !$this->is_ajax() and !$this->check_requirement($action, 'post')) {
               # Store current URL for normal GET requests
               $this->session['return_to'] = Dispatcher::$path;
            }

            $redirect = array(
               'controller' => config('auth_controller'),
               'action'     => 'login',
            );
         }

         # Check if the action requires a logged in admin
         if ($this->check_requirement($action, 'admin') and !self::is_admin()) {
            if ($action == 'index' or !$this->has_action('index')) {
               $redirect = '/';
            } else {
               $redirect = ':';
            }
         }

         if ($redirect) {
            $this->msg['error'] = _("Access denied, please login");
            $this->redirect_to($redirect);
            return false;
         } else {
            return true;
         }
      }

      # Send a cookie to remember the login
      protected function send_auth_token() {
         if ($user = self::auth('current')) {
            $this->send_cookie('auth_token', $user->token.$user->id, array(
               'expire' => time() + 1 * YEAR,
            ));
         }
      }
   }

?>
