<?php
namespace Demo;
include __DIR__ . '/../common.php';
use Gaia\Cache;

/**
* very simple singleton instance of the cache object.
*
* Why do you need a singleton method of instantiation?
* 
* To answer this question, you need to know a little bit about how the memcache client works.
* First of all, none of the memcache servers in your pool know about each other. They rely on the
* clients talking to the server to all agree on where the data will be located on each server. This
* keeps the server layer extremely efficient. The server does one job very well; get and set keys
* into an internal memory set and return them out across the network. 
*
* So, how do all the clients agree on where a particular data value is located? The clients don't talk
* to each other either. The memcache client API hashes keynames against a list of the servers, and 
* uses a kind of modulo (not really, but it helps to visualize what is happening) function to determine
* where a key should go. The hashing function tries to distribute the keys evenly over all the servers
* in the pool as much as possible, so that load is shared evenly. As long as all the clients have
* identical list of servers in the memcache pool, the keys will be hashed the same way, and a key
* written by one client can be accessed by another.
*
* But why bother with a pool of caching servers? Why not just have each webserver write to its own
* memcache instance over localhost? True this would be very fast. But if you are going to do that,
* you should just skip the network layer altogether and use something like Cache\APC. The reason we
* write to a shared pool of cache servers is to get the greatest re-use and synchronization out of 
* our cache. 
*
* When we cache data that changes frequently, we want to have the most current version of the data 
* possible, while caching that data as long as possible. If we know there is only one value in the 
* pool that represents that data, we can refresh and over-write it, or delete it from the cache, and
* be confident that all the other clients will see the very latest version of the data. With a large
* webserver farm, individual caches cannot guarantee this, since each webserver could then possibly
* have its own cache of the data.
*
* With the pool of servers and an agreed upon strategy of hashing the data keys, we can spread our
* cache over many servers while still only keeping one copy of the data in the cache.
*
* Now, back to the original question: why a singleton method of instantiation?
* we want to be certain that every request on every server uses an identical server list, and interacts
* with the client api in the same way. This factory method approach allows us to do that. In addition,
* it also gives me a single spot where I can namespace my cache keys transparently for my development
* environment, so that if I am working with a different set of data than a co-worker, we won't accidentally
* clobber data that is unrelated to our own work.
* 
* TODO: add more info about caching strategies and test data.
*/
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
        self::$memcache = new Cache\Namespaced( new Cache\Memcache, self::cacheprefix() );
        foreach( self::cacheservers() as $entry){
            list( $host, $port, $weight ) = $entry;
            self::$memcache->addServer($host, $port, $weight);
        }
        return self::$memcache;
     }
     
     public static function apc(){
        if( isset( self::$apc ) ) return self::$apc;
        return self::$apc = new Cache\Namespaced( new Cache\Apc, self::cacheprefix() );
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
