<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   if (function_exists('xcache_get') and ini_get('xcache.var_size') > 0) {
      function cache_get($key)          { return xcache_get($key); }
      function cache_set($key, &$value) { xcache_set($key, $value); return $value; }
      function cache_delete($key)       { return xcache_unset($key); }
      function cache_clear()            { return xcache_clear_cache(); }
   } elseif (function_exists('apc_store')) {
      function cache_get($key)          { return apc_fetch($key); }
      function cache_set($key, &$value) { apc_store($key, $value); return $value; }
      function cache_delete($key)       { return apc_delete($key); }
      function cache_clear()            { return apc_clear_cache(); }
   } else {
      $GLOBALS['_CACHE'] = array();
      function cache_get($key)          { return $GLOBALS['_CACHE'][$key]; }
      function cache_set($key, &$value) { $GLOBALS['_CACHE'][$key] = $value; return $value; }
      function cache_delete($key)       { unset($GLOBALS['_CACHE']); }
      function cache_clear()            { $GLOBALS['_CACHE'] = array(); }
   }

?>
