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
*
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
    */
    public static function setName($id, $name ){
        $row = array_pop( self::get( array( $id ) ) );
        if( ! $row ) return FALSE;
        $row['name'] = $name;
        self::cache()->set($id, $row, MEMCACHE_COMPRESSED, self::CACHE_TIMEOUT);
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