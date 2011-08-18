<?php
namespace Demo;
include __DIR__ . '/../common.php';
use Gaia\Cache as GCache;

/**
* very simple singleton instance of the cache object.
*/
class Cache {
   
    // store the singleton cache object here.
    protected static $cache;
    
    /**
    * use this method to use the cache.
    *
    */
    public static function instance(){
        if( isset( self::$cache ) ) return $cache;
        $cache = new GCache\Base;
        
        foreach( self::servers() as $entry){
            list( $host, $port, $weight ) = $entry;
            $cache->addServer($host, $port, $weight);
        }
        return self::$cache = new GCache\Namespaced( $cache, self::prefix() );
     }
     
     // make this function return an appropriate prefix based on whether
     // being used in a test environent. Don't want to overlap with any one
     // else's cache namespace.
     //
     protected static function prefix(){
        return 'demo';
     }
     
    // get the list of servers.
    // normally this will be a huge cluster of servers. 
    // grab it from some global config spot.
    // this is just a demo.
     protected static function servers(){
        return array(
            array('localhost', '11211', '1'),
        );
     }
}

// --------------------------------------------
// DEMO
print_r( new GCache\Replica(Cache::instance(), 3) );