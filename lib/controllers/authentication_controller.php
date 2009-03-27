<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class AuthenticationController extends ApplicationController
   {
      protected $_require_login = array('except' => array('login', 'logout', 'signup'));
      protected $_require_admin = array('index');
      protected $_require_post = array('destroy');

      protected $_scaffold_actions = array('index', 'edit', 'destroy');
      protected $_scaffold_options = array(
         'hide_attributes' => array( 'salt', 'crypted_password'),
      );

      protected function init() {
         $this->_scaffold = config('auth_model');
      }

      function login() {
         if ($this->is_post()) {
            $user = self::auth('authenticate',
               $this->params['username'],
               $this->params['password']
            );

            if ($user) {
               self::auth('login', $user);
               $this->session['auth_id'] = $user->id;

               if ($this->params['remember']) {
                  $this->send_auth_token();
               }

               $this->redirect_back('');
               return $user;
            } else {
               $this->add_error(
                  array('username', 'password'),
                  _('Invalid user name or password')
               );
            }
         }

         $this->render(array($this->name.'/login', 'authentication/login'));
      }

      function logout() {
         self::auth('logout');
         unset($this->session['auth_id']);
         $this->delete_cookie('auth_token');
         $this->redirect_to(':/login');
      }

      function signup() {
         $this->scaffold('create', array(
            'redirect_to' => ':/login',
         ));
      }

      function edit($id) {
         if (is_admin() or self::auth('current')->id == intval($id)) {
            $this->scaffold('edit', $id);
         } else {
            $this->msg['error'] = _("Access denied");
            $this->redirect_to(':');
         }
      }

      function destroy($id) {
         if (is_admin() or self::auth('current')->id == intval($id)) {
            $this->scaffold('destroy', $id);
            if (!self::auth('current')->exists) {
               $this->logout();
            }
         } else {
            $this->msg['error'] = _("Access denied");
            $this->redirect_to(':');
         }
      }
   }

?>
