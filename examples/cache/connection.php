<?php
namespace Demo;
include __DIR__ . '/../common.php';
use Gaia\Cache;

/**
* very simple singleton instance of the cache object.
*/
class Connection {
   
    // store the singleton cache object here.
    protected static $cache;
    
    /**
    * use this method to use the cache.
    *
    */
    public static function cache(){
        if( isset( self::$cache ) ) return self::$cache;
        $cache = new Cache\Base;
        
        foreach( self::cacheservers() as $entry){
            list( $host, $port, $weight ) = $entry;
            $cache->addServer($host, $port, $weight);
        }
        return self::$cache = new Cache\Namespaced( $cache, self::cacheprefix() );
     }
     
     // make this function return an appropriate prefix based on whether
     // being used in a test environent. Don't want to overlap with any one
     // else's cache namespace.
     //
     protected static function cacheprefix(){
        return 'demo';
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
