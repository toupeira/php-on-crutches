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

      static protected function DB() {
         return DB(config('auth_model'));
      }

      static function current() {
         return self::$_current;
      }

      static function login($user) {
         if (is_null($user) or $user instanceof AuthenticationModel) {
            return self::$_current = $user;
         } elseif (is_numeric($user)) {
            return self::login(self::DB()->find($user));
         } else {
            throw new TypeError($user);
         }
      }

      static function logout() {
         return self::$_current = null;
      }

      # Authenticate user with a password
      static function authenticate($username, $password) {
         if (!$username or !$password) {
            return;
         } elseif (is_email($username) and $column = self::DB()->attributes['email'] and $column['unique']) {
            $key = 'email';
         } else {
            $key = 'username';
         }

         if ($user = self::DB()->find($key, $username)) {
            return $user->authenticate_password($password);
         }
      }

      # Authenticate user with a login cookie
      static function authenticate_token($token) {
         $id    = substr($token, 40);
         $token = substr($token, 0, 40);

         if (is_numeric($id) and $user = self::DB()->find($id) and $user->token == $token) {
            return $user;
         }
      }

      # Use virtual attributes to hold the unencrypted password
      protected $_virtual_attributes = array('password', 'password_confirmation');

      function validate() {
         if ($this->new_record or $this->password) {
            $this->has_length('password', 6);
            array_delete($this->_errors, 'salt', 'crypted_password');
         }

         $this->is_confirmed('password');
      }

      function get_admin() {
         if (array_key_exists('admin', $this->_attributes)) {
            return $this->read_attribute('admin');
         } else {
            return false;
         }
      }

      # Generate the cookie token
      function get_token() {
         return sha1("--{$this->id}--{$this->username}--{$this->salt}--{$this->crypted_password}--");
      }

      # Encrypt a password
      function encrypt($password) {
         return sha1("--{$this->salt}--{$password}--");
      }

      # Compare the password to the encrypted password
      function authenticate_password($password) {
         if ($this->crypted_password == $this->encrypt($password)) {
            return $this;
         } else {
            return false;
         }
      }

      protected function before_validation() {
         if ($this->new_record) {
            # Generate the salt for new records
            $this->salt = sha1("--".time()."--{$this->username}--");
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
