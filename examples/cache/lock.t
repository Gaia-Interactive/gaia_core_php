#!/usr/bin/env php
<?php
namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';
// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-lock

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
        return new Cache\Prefix( Connection::memcache(), __CLASS__ . '/');
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