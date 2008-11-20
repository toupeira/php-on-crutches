<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   abstract class AuthenticationModel extends ActiveRecord
   {
      static protected $_current;

      static function model() {
         return config('auth_model');
      }

      static function current() {
         return self::$_current;
      }

      static function login($user) {
         if (is_null($user) or $user instanceof AuthenticationModel) {
            return self::$_current = $user;
         } elseif (is_numeric($user)) {
            return self::login(DB(self::model())->find($user));
         } else {
            throw new TypeError($user);
         }
      }

      static function logout() {
         return self::$_current = null;
      }

      # Authenticate user with a password
      static function authenticate($login, $password) {
         $user = DB(self::model())->find_by_login($login);

         if ($user and $user->crypted_password == $user->encrypt($password)) {
            return $user;
         }
      }

      # Authenticate user with a login cookie
      static function authenticate_token($token) {
         $id    = substr($token, 40);
         $token = substr($token, 0, 40);

         if (is_numeric($id) and $user = DB(self::model())->find($id) and $user->token == $token) {
            return $user;
         }
      }

      # Use virtual attributes to hold the unencrypted password
      protected $_virtual_attributes = array('password', 'password_confirmation');

      function validate() {
         if ($this->new_record or $this->password) {
            $this->has_length('password', 6);
         }

         $this->is_confirmed('password');
      }

      function get_admin() {
         foreach (array('admin', 'is_admin') as $key) {
            if (array_key_exists($key, $this->_attributes)) {
               return $this->read_attribute($key);
            }
         }

         return false;
      }

      # Generate the cookie token
      function get_token() {
         return sha1("--{$this->id}--{$this->login}--{$this->salt}--{$this->crypted_password}--");
      }

      # Encrypt a password
      function encrypt($password) {
         return sha1("--{$this->salt}--{$password}--");
      }

      protected function before_validation() {
         if ($this->new_record) {
            # Generate the salt for new records
            $this->salt = sha1("--".time()."--{$this->login}--");
         }

         # Encrypt the password
         if ($password = $this->password) {
            $this->crypted_password = $this->encrypt($password);
         }

         # Don't allow normal users to change the admin setting
         if (!is_admin()) {
            $this->reset('admin');
         }
      }
   }

?>
