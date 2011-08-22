#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/**
* When writing applications, it is often best to use the 'domain' model approach.(see 
* http://en.wikipedia.org/wiki/Domain_model )The domain controls business logic and caching 
* related to that domain. This means you have many unique peices of data all independently 
* populated into the cache. Unfortunately, this can be very inefficient for requests that aggregate 
* data from many sources of data independently. Each domain model has to be instantiated and queried,
* then all the information assembled into a composite every time the request comes in. 
*
* Caching composite information avoids having to instantiate and query all the individual models.
* Just consume the data from the cache and rebuild it as needed. But what happens when the data changes? 
* If there is only one composite of information, it is easy enough to have each domain model delete
* the cache that is related to the domain and let the cache repopulate. But what if there are many
* different composite datasets related to the changed data in the original domain object? The domain 
* objects have to know about all the related composite cached data. This approach follows the 
* observer - observable pattern ( see http://en.wikipedia.org/wiki/Observer_pattern ). Nice concept, 
* but messy to maintain.
* 
*  We need a way to loosely couple the composite with the domain. We solve this problem using a layer
* of indirection. We attach a revision number to the cache key. When we increment the revision number,
* it changes the cache key, in effect forcing a refresh of the cache. This is a variation on the
* publish - subscribe pattern (see http://en.wikipedia.org/wiki/Publish/subscribe):

* ----------\                /-----------
* publishers > - revision - < subscribers 
* ----------/                \-----------
*
* This pattern allows many different publishers to increment a revision number as things change, and 
* the subscribers use that changed revision number to know they need to update the cache. Now the 
* domain object and the composite cache are decoupled.
*
* An example may help to clarify my point. For example, you might want to cache a user's
* entire profile page. Profile pages are difficult to cache because they usually contain lots
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
        return new Cache\Revision( new Cache\Namespaced( Connection::cache(), __CLASS__ . '/') );
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
        self::cache()->set( $key, $result, MEMCACHE_COMPRESSED, self::CACHE_TIMEOUT );
        
        // return the result
        return $result;
    }
    
    //  instantiate the cache.
    protected static function cache(){
        return new Cache\Namespaced( Connection::cache(), __CLASS__ . '/');
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



