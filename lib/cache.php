<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function cache($key=null, $value=null) {
      if ($value = $GLOBALS['_CACHE_STORE']->get($key)) {
         log_debug("Reading cache fragment '$key'");
         return $value;
      }
   }

   function cache_set($key, $value, $ttl=0) {
      log_debug("Writing cache fragment '$key'");
      return $GLOBALS['_CACHE_STORE']->set($key, $value, $ttl);
   }

   function cache_expire($key) {
      log_debug("Expiring cache fragment '$key'");
      return $GLOBALS['_CACHE_STORE']->expire($key);
   }

   function cache_clear() {
      log_debug("Clearing cache");
      return $GLOBALS['_CACHE_STORE']->clear();
   }

   function cache_eval($key, $code, $ttl=0) {
      if ($data = cache($key)) {
         return $data;
      } else {
         return cache_set($key, eval("return $code;"), $ttl);
      }
   }

   abstract class CacheStore
   {
      function setup() { return true; }
      abstract function get($key);
      abstract function set($key, $value, $ttl=0);
      abstract function expire($key);
      abstract function clear();
   }

   class CacheStoreMemory extends CacheStore
   {
      protected $data = array();

      function get($key)                 { return $this->data[$key]; }
      function set($key, $value, $ttl=0) { return $this->data[$key] = $value; }
      function expire($key)              { unset($this->data[$key]); return true; }
      function clear()                   { $this->data = array(); return true; }
   }

   class CacheStoreApc extends CacheStore
   {
      function setup() {
         return function_exists('apc_store');
      }

      function get($key)                 { return apc_fetch($key); }
      function set($key, $value, $ttl=0) { apc_store($key, $value, $ttl); return $value; }
      function expire($key)              { return apc_delete($key); }
      function clear()                   { return apc_clear_cache(); }
   }

   class CacheStoreXcache extends CacheStore
   {
      function setup() {
         if (!function_exists('xcache_get')) {
            return false;
         } elseif (ini_get('xcache.var_size') == 0) {
            log_warn("Xcache is disabled (set xcache.var_size in php.ini)");
            return false;
         } else {
            return true;
         }
      }

      function get($key)                 { return xcache_get($key); }
      function set($key, $value, $ttl=0) { xcache_set($key, $value, $ttl); return $value; }
      function expire($key)              { return xcache_unset($key); }
      function clear()                   { return xcache_clear_cache(); }
   }

   class CacheStoreFile extends CacheStore
   {
      function setup() {
         $this->path = any(config('cache_path'), TMP.'cache');
         if (is_dir($this->path) and is_writable($this->path)) {
            return true;
         } else {
            if (@mkdir($this->path, 0750, true)) {
               return true;
            } else {
               log_warn("Can't create cache directory '{$this->path}'");
               return false;
            }
         }
      }

      function get($key) {
         if (is_file($path = $this->build_path($key))) {
            return unserialize(@file_get_contents($path));
         }
      }

      function set($key, $value, $ttl=0) {
         file_put_contents($this->build_path($key), serialize($value));
         return $value;
      }

      function expire($key) {
         return rm_f($this->build_path($key));
      }

      function clear() {
         return run("rm -f %s/*.cache", $this->path);
      }

      protected function build_path($key) {
         return $this->path.'/'.trim(basename($key)).'.cache';
      }
   }

?>
