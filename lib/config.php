<?
/*
  PHP on Crutches - Copyright (c) 2008 Markus Koller

  This program is free software; you can redistribute it and/or modify
  it under the terms of the MIT License. See COPYING for details.

  $Id$
*/

  require CONFIG.'framework.php';
  @include CONFIG.config('application').'.php';

  $_CONFIG = array_merge($_FRAMEWORK, (array) $_APPLICATION);

  function config($key) {
    return $GLOBALS['_CONFIG'][$key];
  }

  function config_set($key, $value) {
    $GLOBALS['_CONFIG'][$key] = $value;
  }

?>
