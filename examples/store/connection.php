<?php
namespace Demo;
include __DIR__ . '/../common.php';
use Gaia\Store;
use Gaia\Test\Tap;

// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-connection

class Connection {
   
    // store the singleton cache object here.
    protected static $memcache;
    protected static $apc;
    
    /**
    * use this method to use the cache.
    *
    */
    public static function memcache(){
        if( isset( self::$memcache ) ) return self::$memcache;
        self::$memcache = new Store\Memcache;
        foreach( self::cacheservers() as $info ){
            list( $server, $port, $weight ) = $info;
            $res = self::$memcache->addServer( $server, $port, $weight );
            if( ! $res ) return self::$memcache = new Store\Container;
        }
        return self::$memcache;
     }
     
     public static function apc(){
        if( isset( self::$apc ) ) return self::$apc;
        return self::$apc = new Store\APC;
    }
     
     // make this function return an appropriate prefix based on whether
     // being used in a test environent. Don't want to overlap with any one
     // else's cache namespace.
     //
     protected static function cacheprefix(){
        return 'demo_001/';
     }
     
    // get the list of servers.
    // normally this will be a huge cluster of servers. 
    // grab it from some global config spot.
    // this is just a demo.
     protected static function cacheservers(){
        return array(
            array('localhost', '11211', '1'),
        );
     }
}
