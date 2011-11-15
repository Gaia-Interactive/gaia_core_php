#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Store;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';
include __DIR__ . '/../../tests/assert/date_configured.php';

// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-row-wise

class UserController {

    const CACHE_TIMEOUT = 10;
    
    /**
    * demo of fetching rows from the cache using the row-wise strategy.
    * 
    */
    public static function get( array $ids ){
        return self::cache()->get( $ids );
    }
    
    /*
    * write to the database, and the cache at the same time.
    * since we have no database, we gotta fake it.
    * UPDATE users set name = ? WHERE user_id = ?;
    */
    public static function setName($id, $name ){
        $v = self::get( array( $id ) );
        $row = array_pop( $v );
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
        $options = array(
            'callback'=>array(__CLASS__, 'fromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
        );
        return new Store\Callback( new Store\Prefix( Connection::memcache(), __CLASS__ . '/'), $options);
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
$rows = UserController::get( array( 1 ) );
$row = array_pop( $rows );
Tap::is($row['name'], $name, "fetching username for user 1, got back $name");


Tap::debug( $result );