<?
# Copyright 2008 Markus Koller
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the MIT License.
#
# $Id$
#

   function cache() {
      if ($cache = $GLOBALS['_CACHE']) {
         return $cache;
      } else {
         if ($type = config('cache_store')) {
            $store = ucfirst($type).'Store';
         } else {
            $store = 'MemoryStore';
         }

         if (class_exists($store)) {
            $store = new $store();
            if (!$store->setup() and get_class() != 'MemoryStore') {
               $store = new MemoryStore();
            } elseif (! $store instanceof CacheStore) {
               raise("Invalid cache store '$type'");
            }
            return $GLOBALS['_CACHE'] = $store;
         } else {
            raise("Invalid cache store '$type'");
         }
      }
   }

   abstract class CacheStore
   {
      function __construct() {
         if (!$this->setup() and get_class() != MemoryStore) {
            $GLOBALS[_CACHE];
         }
      }

      function setup() { return true; }
      abstract function get($key);
      abstract function set($key, $value);
      abstract function delete($key);
      abstract function clear();
   }

   class MemoryStore extends CacheStore
   {
      protected $data = array();

      function get($key)          { return $this->data[$key]; }
      function set($key, $value)  { $this->data[$key] = $value; return $value; }
      function delete($key)       { unset($this->data[$key]); return true; }
      function clear()            { $this->data = array(); return true; }
   }

   class ApcStore extends CacheStore
   {
      function setup() {
         return function_exists('apc_store');
      }

      function get($key)          { return apc_fetch($key); }
      function set($key, $value)  { apc_store($key, $value); return $value; }
      function delete($key)       { return apc_delete($key); }
      function clear()            { return apc_clear_cache(); }
   }

   class XcacheStore extends CacheStore
   {
      function setup() {
         if (!function_exists('xcache_get')) {
            return false;
         } elseif (ini_get('xcache.var_size') == 0) {
            log_warn("Xcache is disabled (set xcache.var_size in php.ini)");
            return false;
         }
      }

      function get($key)          { return xcache_get($key); }
      function set($key, $value)  { xcache_set($key, $value); return $value; }
      function delete($key)       { return xcache_unset($key); }
      function clear()            { return xcache_clear_cache(); }
   }

   class FileStore extends CacheStore
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
         return true;
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
