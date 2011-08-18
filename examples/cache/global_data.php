<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

class SiteController {

    public static function config(){
        $cacher = self::cache();
        $key = 'config';
        $data = $cacher->get($key);
        if( $data ) return $data;
        
        // fake data ... here is where you would connect and fetch data from the database.
        $data = array('date'=>date('Y/m/d H:i:s'), 'time'=>time(), 'foo'=>'bar', 'bazz'=>'quux');
        
        // cache for 10 seconds, gzip the data transparently so less overhead over the wire.
        // only caching for a short time for demo purposes, so you can see it refresh.
        // the date and time values will change each time the cache is refreshed.
        $cacher->set($key, $data, MEMCACHE_COMPRESSED, 10);
        
        // all done.
        return $data;
        
    }
    
    // use singleton instantiation, like in ./connection.php
    // this is just for demo purposes.
    protected static function cache(){
        return new Cache\Namespaced( new Cache\Replica(Connection::cache(), 3), __CLASS__ . '/');
    }
}

// ---------------------------------

$data = SiteController::config();

Tap::plan(1);
Tap::ok(is_array( $data ), 'sitecontroller::config() returned an array of data ');
Tap::debug( $data, 'test');