<?
/*
   PHP on Crutches - Copyright (c) 2008 Markus Koller

   Permission is hereby granted, free of charge, to any person obtaining
   a copy of this software and associated documentation files (the
   "Software"), to deal in the Software without restriction, including
   without limitation the rights to use, copy, modify, merge, publish,
   distribute, sublicense, and/or sell copies of the Software, and to
   permit persons to whom the Software is furnished to do so, subject to
   the following conditions:

   The above copyright notice and this permission notice shall be
   included in all copies or substantial portions of the Software.

   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
   EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
   MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
   NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
   LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
   OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
   WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

   $Id$
*/

   # Load standard libraries
   $libs = array(
      'base',
      'logger',
      'config',

      'dispatcher',
      'controller',
      'model',
      'view',
   );

   foreach ($libs as $lib) {
      require LIB."$lib.php";
   }

   # Load standard helpers
   $helpers = glob(LIB.'helpers/*.php');
   foreach ($helpers as $helper) {
      require $helper;
   }

   # Load application helper
   @include_once HELPERS.'application_helper.php';

   # Load route definitions
   @include CONFIG.'routes.php';

   # Load database definitions
   @include CONFIG.'database.php';

   # Load database support if necessary
   if (!empty($_DATABASE)) {
      foreach (glob(LIB.'database/*.php') as $lib) {
         require $lib;
      }
   }

   # Initialize the framework
   Logger::init();
   Dispatcher::init();

?>
