#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/**
* When writing caching solutions, the preferred approach is to have a single 'domain' model (see 
* http://en.wikipedia.org/wiki/Domain_model ) to control the business logic and caching for everything
* related to that relevant domain. But sometimes, the real world is messier than that. Sometimes it
* is best to cache large chunks of aggregated information. For example, you might want to cache a
* user's entire profile page. Profile pages are difficult to cache because they usually contain lots
* of information from many different tables. This means they are also expensive to render. If I cache 
* the entire profile page, I skip instantiating all those different objects and making all those queries.
* I can get it all from one cache key.
*
* But what if information changes? This is where revisions come in handy. The cache revision is a layer 
* of indirection in the cache key. Normally when constructing a cache key for something, I might do 
* something like:
*    /user/profile/{$user_id}
* But I can bust the cache, if I also append a revision to that key. Every time I change something related 
* to that page, I make sure to also increment the revision. 
*    /user/profile/{$user_id}/rev/{$rev}
* I can use another cache key to store that revision. This means each time I want to load the profile
* page cache, I must first get the revision that is associated with it.
*
* There are some tricks to make this work though. If all I do is increment a cache key for the revision 
* number, what happens if the revision number gets evicted from the cache? Instead of using a simple 
* incrementing id, I can instead create a revision out of the current time, along with another integer 
* that is unlikely to collide with other numbers in our server farm. Now, even if the revision gets 
* evicted from the cache, I wont accidentally repeat an old revision number and get a stale version of 
* a cached page.
*/
class UserRev {
    
    public static function get( $id, $refresh = FALSE ){
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

class UserName {
    
    static protected $names = array();
    
    public static function get( $id ){
        return self::$names[ $id ];
    }
    
    public static function set( $id, $name ){
        self::$names[ $id ] = $name;
        UserRev::get( $id, TRUE );
        return $name;
    }
}


class UserMessage {
    
    protected $user_id;
    protected $message;
    const CACHE_TIMEOUT = 30;
    
    function __construct( $user_id, $message ){
        $this->user_id = $user_id;
        $this->message = $message;
    }
    
    function render(){
        $key = $this->user_id .'/' . UserRev::get( $this->user_id );
        $result = self::cache()->get( $key );
        if( $result ) return $result;
        $result = sprintf("<h1>%s, %s, today at %s (%s)<h1>", 
            $this->message,
            UserName::get( $this->user_id ), 
            date('Y/m/d H:i:s'), 
            microtime(TRUE));
        self::cache()->set( $key, $result, MEMCACHE_COMPRESSED, self::CACHE_TIMEOUT );
        return $result;
    }
    
    protected static function cache(){
        return new Cache\Namespaced( new Cache\Replica(Connection::cache(), 3), __CLASS__ . '/');
    }
}

// ----
// DEMO 

Tap::plan(7);
$user_id = 1;
Username::set($user_id, $name = 'billy' );
$hello = new UserMessage( $user_id, 'hello');
$goodbye = new UserMessage( $user_id, 'hello');



Tap::like( $before = $hello->render(), '/billy/', 'before, hello message has the name billy in it');
UserName::set( $user_id, $name = 'bob' );
Tap::like($after = $hello->render(), '/bob/', 'after name change, hello message has the name bob in it');
Tap::is( $after, $hello->render(), 'when I load the message again, I get cached content');

Tap::debug( $before, 'before name change');
Tap::debug( $after, 'after name change');

Tap::like( $before = $goodbye->render(), '/bob/', 'before, goodbye message has the name bob in it');
UserName::set( $user_id, $name = 'bubba' );
Tap::like($after = $goodbye->render(), '/bubba/', 'after name change, goodbye message has the name bubba in it');
Tap::is( $after, $goodbye->render(), 'when I load the message again, I get cached content');

UserRev::get( $user_id, TRUE );
Tap::isnt( $after, $goodbye->render(), 'when I load the message after cache busting, I get new content');



