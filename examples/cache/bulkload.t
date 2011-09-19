#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';
// see https://github.com/gaiaops/gaia_core_php/wiki/cache-bulk-load


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
        return  new Cache\Callback( new Cache\Prefix( Connection::memcache(), __CLASS__ . '/'));
    }
}

// ----
Tap::plan(1);

$user = new User(1);
$users = User::load( array(1,2,3) );
Tap::is( $users[1], $user, 'the user row returned on single instantiation is the same as the cached row in multi load');
Tap::debug( $user, 'constructor instantiated' );
Tap::debug( $users, 'bulk load' );