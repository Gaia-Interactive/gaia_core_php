#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Store;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/../../tests/assert/date_configured.php';
include __DIR__ . '/connection.php';

// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-global-data

class SiteConfig {

    // only caching for a short time for demo purposes, so you can see it refresh.
    const CACHE_TIMEOUT = 10;

    public static function data(){
    
        // get the cache object, which is a prefix replica cache object.
        $cacher = self::cache();
        
        // name the cache key after the function we are using.
        $key = __FUNCTION__;
        
        // look for the data in the cache first.
        $data = $cacher->get($key);
        if( $data ) return $data;
        
        // fake data ... here is where you would connect and fetch data from the database.
        // QUERY: SELECT name, value FROM config;
        $data = array('foo'=>'bar', 'bazz'=>'quux', 'modified'=>date('Y/m/d H:i:s'), );
        
        // the date and time values will change each time the cache is refreshed.
        $cacher->set($key, $data, self::CACHE_TIMEOUT);
        
        // all done.
        return $data;
        
    }
    
    // wrapper method for instantiating a multi-tier caching system, with apc in front of memcache.
    protected static function cache(){
        return new Store\Prefix( 
                new Store\Tier( 
                    new Store\Gate( Connection::memcache() ),
                    Connection::apc()
                ), __CLASS__ . '/');
    }
}

// ---------------------------------

$data = SiteConfig::data();

Tap::plan(1);
Tap::ok(is_array( $data ), 'globaldata::config() returned an array of data ');
Tap::debug( $data, 'result set from config');
