#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/**
* This example illustrates how to cache rows of data in a reusable manner. When first learning to
* cache data, developers will fetch all the rows they need from the database and write it all into 
* a single cache key. This works great to cache the data needed for that individual request and any
* others that are identical. But what about caching overlapping data sets?
* 
* For example, I have user information that I cache for a page where a bunch of users reply to a 
* message board. If I put all of that user information into one cache key, It is likely I can only 
* use that cached data on that one page. But what about thousands of other pages where different
* combinations of those same users are commenting on other messages? I end up duplicating lots of 
* information into my cache. Worse, what if user information changes after I have cached the data?
* With all that cached data spread out over thousands of keys, there is no easy way to refresh the 
* cache.
*
* (See ''./revisions.php'' for more info about good ways to refresh many cache keys at once).
*
* But if each user row is assigned its own key in the cache, we don't duplicate any information
* in the cache, and can easily keep the cache up-to-date. With memcache's multi-key fetching abilities
* we can get 10 keys out of the cache in the same amount of time it takes to get 1 key out of the
* cache. The memcache client is smart enough to fetch all the keys you request in parallel across 
* the network, even if the keys are hashed across many different servers.
*
* Now that we have populated user information into the cache by id, we can ask for different users
* from the cache, and only ask the database for the missing keys. This sounds easy to do, but when
* you start coding this from scratch using only the base memcache object, you find you have to write
* a whole lot of code to accomplish this. First, you have to prefix all of your memcache keys with a 
* name that won't collide with any other keys in the cache. Then you have to check your cache results
* to see which keys came back empty and parse the name to extract the user id and figure out which
* key is not there. Then you have to query the database for those users, and re-generate the key names
* and populate it back into the cache. This process is very cumbersome and error prone. 
* 
* The Cache\Namespaced class provides nice hooks for you to be able to do these steps easily with very
* few steps. We use the namespaced functionality to prefix all of the memcache keys automatically with
* a string of our choosing, by passing that prefix to the constructor. Thereafter we only have to 
* use the ids themselves that we pass to the database. Next, we provide a callback hook that allows
* the cache object to pass all the ids that are missing in the cache to a method of your choice and
* use it to re-populate the cache.
* 
* Clarifying note: I wrote this class as a static class function library because it is simpler
* for you to understand. But you can use instantiated objects and callback handlers to populate
* the cache if you wish:
* $options = array('callback'=>array( $this, 'fromDB' ), 'timeout'=>60);
* this would work just fine as well. Any valid php callback method is fine. For more information,
* on callbacks in php:
* @see http://php.net/is_callable
* @see http://www.php.net/manual/en/language.pseudo-types.php#language.types.callback
*/
class UserController {

    const CACHE_TIMEOUT = 10;
    
    /**
    * demo of fetching rows from the cache using the row-wise strategy.
    * 
    */
    public static function get( array $ids ){
        $options = array(
            'callback'=>array(__CLASS__, 'fromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
            'compression'=>MEMCACHE_COMPRESSED,
        );
        
        return self::cache()->get( $ids, $options );
    }
    
    /*
    * write to the database, and the cache at the same time.
    * since we have no database, we gotta fake it.
    * UPDATE users set name = ? WHERE user_id = ?;
    */
    public static function setName($id, $name ){
        $row = array_pop( self::get( array( $id ) ) );
        if( ! $row ) return FALSE;
        $row['name'] = $name;
        self::cache()->set($id, $row, self::CACHE_TIMEOUT);
        return TRUE;
    }
    
    /**
    * this is the callback handler.
    * normally you would use the ids passed to this function
    * to look up the users from the database, and return the info you need.
    * only the ids not found in the cache will be passed to this function.
    * a query might look something like:
    * SELECT * FROM users WHERE user_id IN( ?, ?, ?, ? );
    * ignore the internals of this method. it is all just fake data used for
    * demo purposes to illustrate the caching strategy found in the other methods of
    * this class.
    */
    public static function fromDB( array $ids ){
        $names = array('barney', 'jill', 'wanda');
        $usercount = count( $names );
        $result = array();
        foreach( $ids as $id ){
            if( $id > $usercount ) continue;
            $result[ $id ] = array('id'=>$id, 'name'=> $names[ $id - 1 ], 'visit'=>date('Y/m/d H:i:s'));
        }
        return $result;
    }
    
    /**
    * singleton method for cache object.
    */
    protected static function cache(){
        return new Cache\Namespaced( Connection::cache(), __CLASS__ . '/');
    }

}

// ----
// DEMO BELOW

Tap::plan(6);

$result = UserController::get( range(1, 5) );
$result2 = UserController::get( range(2, 1) );
$found = TRUE;
foreach( $result2 as $k => $v ){
    if( $result[ $k ] != $v ) $found = false;
}

Tap::ok( is_array( $result), 'UserController::get() returns an array of results');
Tap::is(count($result), 3, 'got back 3 records, 2 missing');
Tap::is( count($result2), 2, 'got back 2 records in second search');
Tap::ok( $found, 'different ordered keys returns same result set out of the cache');

Tap::ok(UserController::setName(1, $name = 'barney-' . time()), 'successfully overwrote name of user 1');
$row = array_pop( UserController::get( array( 1 ) ) );
Tap::is($row['name'], $name, "fetching username for user 1, got back $name");


Tap::debug( $result );