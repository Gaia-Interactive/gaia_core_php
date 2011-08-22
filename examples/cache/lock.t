#!/usr/bin/env php
<?php
namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/**
* This example illustrates a cheap way to use the cache to obtain an advisory lock on a particular 
* task. When building an application where many users may minipulate the same set of data, 
* asynchronously, many race conditions can occur.
*
* A race condition is when two different asynchronous processes attempt to perform competing tasks
* on a single unit of data at the exact same momemnt of time. Most applications perform CRUD operations
* on data, where they read a row from a table, then update or delete it. But what if another process
* is trying to do the same thing at the exact same moment? Several possibilities could happen:
*
* Consider if process A and process B read the same row from the db at the same time. Process A makes
* a change and writes the value to the database. Process B doesn't see the change introduced by Process 
* A. It makes a different change, and writes the value to the database. In this scenario, the change 
* made by Process A vanishes. It becomes a phantom write that disappears when Process B clobbers the 
* change and overwrites it.
*
* There are many other race conditions that can occur in an application. Games are often plagued by
* race conditions when they design a rare item that can be claimed by any one of many clients. If the
* item drop is unique in the game, and many users can potentially claim it, how to decide which one
* actually gets the item? A novice programmer might first check to see if the item has been claimed 
* in the database, and if not, proceeed to mark the row as claimed by the user.  However, if two 
* or more competing processes both attempt to claim it at the same time, they each will think they
* have the right to claim it since they both read at the same time. Then as they mark it as claimed,
* both updates will succeed but one will clobber the other. You can get around this by using the 
* database as your mutex and running a query like this:
*    UPDATE givaway SET claimed = ? WHERE item_id = ? AND claimed IS NULL;
* Then check to see if affected rows is 0, or read after the update to verify that claimed equals 
* the value in the update statement. This is probaly a good practice, but if many thousands of clients
* are competing for the same scare row, you create a hotspot in the database with high row-lock
* contention. 
*
* Our goal is to move the hard work away from the database when possible. To that end, we can obtain
* a cheap advisory lock from the cache instead to make sure we are the winner in the race, before asking 
* the database to doing any work. We do this by doing a memcache::add(). Memcache will only write the
* value into the cache if the key doesn't exist already. This allows us to design a very cheap and 
* distributed locking mechanism. It isn't 100% reliable. Since it is built on top of a cache, and any
* given cache node may be down at any given time temporarily, there is the possibility of false results.
* However, we can assume that for the most part it will be pretty accurate for short periods of time.
* 
* That is all we need when trying to avoid race conditions in our application and prevent them from
* piling up on the databae and causing performance issues or data loss from phantom writes.
*/
class Mutex {
    // short lock time ... probably don't want a lock time longer than a minute or two anyway.
    const LOCK_TIMEOUT = 10;
    
    // unique name in the application for the piece of data being manipulated.
    protected $mutex;
    
    // are we locked or not.
    protected $locked = FALSE;
    
    // class constructor.
    function __construct( $mutex ){
        $this->mutex = $mutex;
    }
    
    // claim the lock
    public function claim(){
        if( ! self::cache()->add( $this->mutex, 1, self::LOCK_TIMEOUT ) ) {
            if( self::cache()->get( $this->mutex ) ) return FALSE;
        }
        $this->locked = TRUE;
        return TRUE;
    }
    
    // release the lock
    public function release(){
        if( ! $this->locked ) return FALSE;
        self::cache()->delete( $this->mutex );
        $this->locked = FALSE;
        return TRUE;
    }
    
    // singleton cache instantiation factory method.
    protected static function cache(){
        return new Cache\Namespaced( Connection::cache(), __CLASS__ . '/');
    }
}

// ----
// DEMO

Tap::plan(4);

$mutex = time();

$client1 = new Mutex( $mutex );
$client2 = new Mutex( $mutex );

Tap::ok( $client1->claim(), 'first client is off and running!');
Tap::ok( ! $client2->claim(), 'second client fails since first claimed it');
Tap::ok( $client1->release(), 'first client ending');
Tap::ok( $client2->claim(), 'now second client can go');