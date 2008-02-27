<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function cache($key=null, $value=null) {
      if ($cache = $GLOBALS['_CACHE_STORE']) {
         if ($value) {
            log_debug("Caching '$key'");
            return $cache->set($key, $value);
         } elseif ($key) {
            return $cache->get($key);
         } else {
            return $cache;
         }
      }
   }

   abstract class CacheStore
   {
      function setup() { return true; }
      abstract function get($key);
      abstract function set($key, $value);
      abstract function delete($key);
      abstract function clear();
   }

   class CacheStoreMemory extends CacheStore
   {
      protected $data = array();

      function get($key)          { return $this->data[$key]; }
      function set($key, $value)  { return $this->data[$key] = $value; }
      function delete($key)       { unset($this->data[$key]); return true; }
      function clear()            { $this->data = array(); return true; }
   }

   class CacheStoreApc extends CacheStore
   {
      function setup() {
         return function_exists('apc_store');
      }

      function get($key)          { return apc_fetch($key); }
      function set($key, $value)  { apc_store($key, $value); return $value; }
      function delete($key)       { return apc_delete($key); }
      function clear()            { return apc_clear_cache(); }
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

      function get($key)          { return xcache_get($key); }
      function set($key, $value)  { xcache_set($key, $value); return $value; }
      function delete($key)       { return xcache_unset($key); }
      function clear()            { return xcache_clear_cache(); }
   }

   class CacheStoreFile extends CacheStore
   {
      function setup() {
         $this->path = any(config('cache_path'), TMP.'cache');
         if (is_dir($this->path) and is_writable($this->path)) {
            return true;
         } else {
            if (mkdir($this->path, 0750, true)) {
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

      function set($key, $value) {
         file_put_contents($this->build_path($key), serialize($value));
         return $value;
      }

      function delete($key) {
         return unlink($this->build_path($key));
      }

      function clear() {
         return run("rm -f %s/*.cache", $this->path);
      }

      protected function build_path($key) {
         return $this->path.'/'.trim(basename($key)).'.cache';
      }
   }

?>
