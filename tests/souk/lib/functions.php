<?php


function memcache(){
    static $cacher;
    if( isset( $cacher ) ) return $cacher;
    $cacher = new \Gaia\Store\Memcache;
    $cacher->addServer('127.0.0.1', '11211');
    return $cacher;
}

function cachemock(){
    static $cacher;
    if( isset( $cacher ) ) return $cacher;
    $cacher = new \Gaia\Store\KVPTTL;
    return $cacher;
}

function uniqueNumber( $start = 1, $end = 100000000000000 ){
    static $used;
    if( ! isset( $used ) ) $used = array();
    do {
        $number = mt_rand( $start, $end );
    } while( isset( $used[ $number ] ) );
    $used[ $number ] = 1;
    return $number;
}

function uniqueUserId(){
    return Gaia\Time::now() . mt_rand(100000, 999999);
    
}

function advanceCurrentTime($secs = 1 ){
    \Gaia\Time::offset( $secs );
}

function lastElement( array $arr ){
    return array_pop( $arr );
}