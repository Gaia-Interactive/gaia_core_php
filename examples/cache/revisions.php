#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';


class UserInfo {
    
    static protected $names = array();
    
    public static function getName( $id ){
        return self::$names[ $id ];
    }
    
    public static function setName( $id, $name ){
        self::$names[ $id ] = $name;
        self::rev( $id, TRUE );
        return $name;
    }
    
    public static function rev( $id, $refresh = FALSE ){
        $cacher = self::cache();
        $key = '/rev/' . $id;
        $res = ( ! $refresh ) ? $cacher->get($key) : '';
        if( strlen( strval( $res ) ) < 1 ){
            $cacher->set($key, $res = time() .'.' . mt_rand(0, 1000000) + posix_getpid(), 0 );
        }
        return $res;
    }
    
    protected static function cache(){
        return new Cache\Namespaced( new Cache\Replica(Connection::cache(), 3), __CLASS__ . '/');
    }

}


class UserPage {
    
    protected $user_id;
    const CACHE_TIMEOUT = 30;
    
    function __construct( $user_id ){
        $this->user_id = $user_id;
    }
    
    function render(){
        $key = $this->user_id .'/' . UserInfo::rev( $this->user_id );
        $result = self::cache()->get( $key );
        if( $result ) return $result;
        $result = sprintf("<h1>hello, %s, today at %s (%s)<h1>", UserInfo::getName( $this->user_id ), date('Y/m/d H:i:s'), microtime(TRUE));
        self::cache()->set( $key, $result, MEMCACHE_COMPRESSED, self::CACHE_TIMEOUT );
        return $result;
    }
    
    protected static function cache(){
        return new Cache\Namespaced( new Cache\Replica(Connection::cache(), 3), __CLASS__ . '/');
    }
}

// ----
// DEMO 

Tap::plan(3);
$user_id = 1;
UserInfo::setName($user_id, $name = 'billy' );
$page = new UserPage( $user_id );
$before = $page->render();
UserInfo::setName( $user_id, $name = 'bob' );
$after = $page->render();
$again = $page->render();
Tap::like($before, '/billy/', 'before, page has the name billy in it');
Tap::like($after, '/bob/', 'after, page has the name bob in it');
Tap::is( $after, $again, 'when I load the page again, get the same content');

Tap::debug( $before, 'before name change');
Tap::debug( $after, 'after name change');


