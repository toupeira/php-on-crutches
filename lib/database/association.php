<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   class Association extends Object
   {
      protected $key;
      protected $class;

      function __construct($key, $class) {
         $this->key = $key;
         $this->class = $class;
      }

      function load($model) {
         raise("Association doesn't implement 'load'");
      }
   }

?>
