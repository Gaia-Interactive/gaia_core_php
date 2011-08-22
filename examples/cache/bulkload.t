#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/*
* this example demonstrates the case where a user row is self-loaded on instantiation.
* A common in pattern where the object always represents the data as well as manipulates
* it. This works well when only manipulating one row of data:
*
* $user = new User( $id );
*
* But what if you need to load many users at once? Most programmers when faced 
* with this problem, will do something like:
*
*  foreach( $ids as $id ) $users[ $id ] = new User( $id );
*
* THIS IS BAD! Why? because each time you instantiate the object, you make another call to the 
* cache and another call to the database if the cache is empty. That means another trip across
* the network to the cache and to the database each time. Both the database and the cache allow
* us to ask for many rows of data at once. 
* 
* We need a pattern where we can do this, but still keep the data encapsulation. Here's how it is
* done:
* 
* $users = User::load( $ids );
*
* I refactor my class constructor to allow a row of data to be passed in instead of just the id. Then
* i move all the caching and query logic out of the constructor and into methods that can load many
* rows at once. My original example still works and returns a cached row. But now I can efficiently
* query the cache and database for many rows of data in only two network calls ... one for the cache
* and one for the database.
*/


class User {
    const CACHE_TIMEOUT = 10;

    protected $data = array();
    
    /**
    * Overloaded constructor.
    * if you pass it an integer, it will load the user information for that user id.
    * if you pass an array, it populates the user information for that user.
    * This allows us to have the static load method construct objects and hydrate them with
    * information bulk loaded from the cache and the database.
    */
    public function __construct($v){
        if( is_array( $v ) ){
            $this->data = $v;
        } else {
            $this->data = array_pop( $this->loadData( array( $v ) ) );
        }
    }
    
    /**
    * load a bunch of users at once.
    */
    public static function load( array $ids ){
        $objects = array();
        foreach( self::loadData( $ids ) as $id => $data ){
            $objects[ $id ] = new self( $data );
        }
        return $objects;
    }
    
    protected static function loadData( array $ids ){
        $options = array(
            'callback'=>array(__CLASS__, 'fromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
            'compression'=>MEMCACHE_COMPRESSED,
        );
        return self::cache()->get( $ids, $options );
    }
    
    /**
    * faking the database here, to keep the example simple. query might look something like:
    * SELECT name, id FROM users where id IN (?, ?, ? ....)
    * return a result set with each row keyed by the user id.
    */
    public static function fromDB( array $ids ){
        $names = array(1=>'fred', 2 =>'marla', 3=>'punky');
        $rows = array();
        foreach( $ids as $id ){
            $rows[ $id ] = array('id'=>$id, 'name'=>$names[ $id ], 'visit'=>date('Y/m/d H:i:s'), 'micro'=>microtime(TRUE));
        }
        return $rows;
    }
    
   /**
    * singleton method for cache object.
    */
    protected static function cache(){
        return new Cache\Namespaced( Connection::memcache(), __CLASS__ . '/');
    }
}

// ----
Tap::plan(1);

$user = new User(1);
$users = User::load( array(1,2,3) );
Tap::is( $users[1], $user, 'the user row returned on single instantiation is the same as the cached row in multi load');
Tap::debug( $user, 'constructor instantiated' );
Tap::debug( $users, 'bulk load' );