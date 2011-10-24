#!/usr/bin/env php
<?php
namespace Demo;
use Gaia\Store;
use Gaia\Test\Tap;
include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-stack

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
        return new Store\Stack( new Store\Prefix( Connection::memcache(), $app ) );
    }
}

Tap::plan(1);

Chatter::add('hello');
Chatter::add('goodbye');

Tap::is( $recent = Chatter::recent(),  array(2=>'goodbye', 1=>'hello'), 'got back a list of hello and goodbye');

Tap::debug( $recent );
