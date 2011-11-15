#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Store;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';
include __DIR__ . '/../../tests/assert/date_configured.php';

// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-revisions

class UserRev {
    
    /**
    * get a revision number. if one doesn't exist yet in the cache, auto-populate a new value
    * into the cache.  If the refresh flag is specified, increment to a new revision.
    */
    public static function get( $id, $refresh = FALSE ){
        return self::rev()->get( $id, $refresh );
    }
    
    /*
    * factory method of instantiation of the cache.
    */
    protected static function rev(){
        return new Store\Revision( new Store\Prefix( Connection::memcache(), __CLASS__ . '/') );
    }

}

/**
* This is just a dummy class that illustrates how a class might increment a rev. You can have many 
* different objects that can increment the revision, and many objects that rely on that revision to 
* create a cache key. This class is and example of a publisher mentioned earlier.
*/
class UserName {
    
    static protected $names = array();
    
    public static function get( $id ){
        // look for a value in the persistent store ... since we don't have a database, fake it 
        // with a static array. if we did have a database, we'd do:
        // QUERY: SELECT name from users WHERE id = ?
        // note, this data would probably be loaded into a row-wise cache ( see ./row_wise.php ) but 
        // not necessary to illustrate the publish/subscribe relationship.
        return self::$names[ $id ];
    }
    
    public static function set( $id, $name ){
        // fake the data into a persistent store.
        // just putting it into a static array, but if we had a database, we'd do something like ...
        // QUERY: UPDATE users set name = ? WHERE id = ?;
        self::$names[ $id ] = $name;
        
        // increment the cache revision number so any subscriber caches are refreshed.
        UserRev::get( $id, TRUE );
        
        // return the name we set to.
        return $name;
    }
}

/**
* This is an example of a subscriber of the cache revision. It never increments the revision on its
* own, but will update its own idependent cache if the revision changes.
*/
class UserMessage {
    
    protected $user_id;
    protected $message;
    const CACHE_TIMEOUT = 30;
    
    function __construct( $user_id, $message ){
        $this->user_id = $user_id;
        $this->message = $message;
    }
    
    function render(){
        // build a cache key based on the user id, and the cache revision for that user.
        $key = $this->user_id .'/' . UserRev::get( $this->user_id );
        
        // grab the data out of the cache.
        $result = self::cache()->get( $key );
        
        // did the cache have what we need? if so, return it.
        if( $result ) return $result;
        
        // build the result. This short string represents what you might do if you were rendering 
        // an entire page of information for the user. keeping it short so you can see what's happening,
        // but this result might come from the output buffer after you render the page using a template 
        // system.
        $result = sprintf("<h1>%s, %s, today at %s (%s)<h1>", 
            $this->message, UserName::get( $this->user_id ), date('Y/m/d H:i:s'),  microtime(TRUE));
            
        // write it back into the cache.
        self::cache()->set( $key, $result, self::CACHE_TIMEOUT );
        
        // return the result
        return $result;
    }
    
    //  instantiate the cache.
    protected static function cache(){
        return new Store\Prefix( Connection::memcache(), __CLASS__ . '/');
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



