#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/*
* This class demonstrates one of the most simple concepts of caching ... how to take a big chunk
* of data that doesn't change often and cache it. This takes a lot of load off the database server
* and distributes it over a pool of memcache servers.
*
* The Cache\Replica class uses a probabilistic approach to refreshing the cache and avoiding the
* problem of the 'thundering herd'. The most common approach to refreshing data in the cache is to 
* let the cache expire. The next client to ask for the data sees that it is missing and repopulates
* it back into the cache. This approach has the benefit of having to only code the logic for updating 
* the cache in one spot. It is easy to understand and maintain. It doesn't rely on cronjobs or other 
* external mechanisms to maintain the data. And if for whatever reason the data gets evicted from the 
* cache, the code will auto-repopulate it. But the strategy has a big problem. When many clients
* attempt to access a cache key in parallel and the data is missing from the cache, there is a race 
* condition. This race condition is known as the 'Thundering Herd'. All of the clients stampede over
* each other to try to repopulate the data back into the cache. 
*
* When this happens, you will see a flurry of database connections stack up on the database server in
* regular intervals. Worse, since the query hasn't been run by the database server in a while, the query
* cache or innodb buffer pool may not have easy access to the data. It may have to hit the disk. If 
* the query is poor performing (often a reason it is cached) the problem is that much worse. All the 
* clients sit around waiting while the database attempts to access data off the disk and calculate the
* results of the query. The highly parallel stampede of clients can even topple and crash a Database
* server in the worst case scenario.
*
* Cache\Replica attempts to solve this problem as much as it can. It keeps many copies of the data 
* available in the cache. This spreads the load of a single 'hot' key in the cache around to many servers
* and makes it less likely that the cache key will disappear due to network instability or temporary
* server outages. Cache\Replica also elects just one client to refresh the data periodically. It does
* this transparently by caching the data forever and holding onto a soft timeout value in the cache.
* when the soft timeout is reached the Replica class tells one client that no data was found, relying
* on that client to know what to do to re-populate the cache. It uses some other nice tricks for
* performance like probabilistic cache refreshing to avoid the overhead of network mutex locks on 
* the cache key. 
*
* The important thing to take away from this example is this: data that is used heavily in your 
* application and changes infrequently should be cached for as long as possible while keeping 
* closely in-sync with your database. Cache\Replica provides a nice API for reducing the likelihood 
* of the 'thundering herd' problem when the data needs to be refreshed.
*/

class GlobalData {

    // only caching for a short time for demo purposes, so you can see it refresh.
    const CACHE_TIMEOUT = 10;

    public static function config(){
    
        // get the cache object, which is a namespaced replica cache object.
        $cacher = self::cache();
        
        // name the cache key after the function we are using.
        $key = __FUNCTION__;
        
        // look for the data in the cache first.
        $data = $cacher->get($key);
        if( $data ) return $data;
        
        // fake data ... here is where you would connect and fetch data from the database.
        // QUERY: SELECT name, value FROM config;
        $data = array('foo'=>'bar', 'bazz'=>'quux', 'modified'=>date('Y/m/d H:i:s'), );
        
        // gzip the data transparently so less overhead over the wire.
        // the date and time values will change each time the cache is refreshed.
        $cacher->set($key, $data, self::CACHE_TIMEOUT);
        
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

$data = GlobalData::config();

Tap::plan(1);
Tap::ok(is_array( $data ), 'globaldata::config() returned an array of data ');
Tap::debug( $data, 'result set from config');