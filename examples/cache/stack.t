#!/usr/bin/env php
<?php
namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/**
* Developers often need to make temporary lists of data. Sometimes those lists can be lossy, and 
* impermanent. An example might be a shoutbox function on a page, where we want to allow people to
* post quick rants around a topic similar to a group chat function on instant message. We have no 
* interest in keeping those comments around long, and if we lose a few, no big loss. The Cache\Stack 
* is a very inexpensive solution to this problem.
* 
* The tough thing about implementing a shoutbox is that it has a high degree of contention over a
* single key. I saw a developer implement this feature by serializing a list of comments to a single 
* cache key. Every time someone posted, he would pull down the cached value, append the item to the 
* list, and then write the list back to the cache. This introduces a race condition. If two clients 
* read and write at the same time, one of their comments will be lost. 
* 
* Cache\Stack works by incrementing a counter in the cache and using the most recent value from the 
* counter as the name of a new cache key, to store that item in the list. Since all of the items in 
* the list are in order, The Cache\Stack class just needs to know the value of the counter to predict 
* the key names of all the previous items in the list. It then fetches back the range of items it needs 
* based on that value using a multi-get.
*/


class Chatter {

    public static function add( $message ){
        return self::stack()->add( $message );
    }
    
    public static function recent( $limit = 10 ){
        return self::stack()->recent( $limit );
    }
    
    /**
    * create a new stack for every time we run this script (that's what microtime is about).
    * That way we will get a fresh list so we can run the test and you can see what's going on
    * without being confused by previous runs of the script.
    */
    protected static function stack(){
        static $app;
        if( ! isset( $app ) ) $app = __CLASS__ . '/' . microtime(TRUE) . '/';
        return new Cache\Stack( new Cache\Namespaced( Connection::memcache(), $app ) );
    }
}

Tap::plan(1);

Chatter::add('hello');
Chatter::add('goodbye');

Tap::is( $recent = Chatter::recent(),  array(2=>'goodbye', 1=>'hello'), 'got back a list of hello and goodbye');

Tap::debug( $recent );
