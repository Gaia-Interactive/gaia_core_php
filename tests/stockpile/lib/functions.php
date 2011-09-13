<?php
namespace Gaia\Stockpile;


function memcache(){
    static $cacher;
    if( isset( $cacher ) ) return $cacher;
    $cacher = new \Gaia\Cache\Memcache;
    $cacher->addServer('127.0.0.1', '11211');
    return $cacher;
}

function quantify( $v ){
    return  Base::quantify( $v );
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
    return Base::time() . mt_rand(100000, 999999);
    
}

function advanceCurrentTime($secs = 1 ){
    Base::$time_offset+= $secs;
    \Gaia\NewID\TimeRand::$time_offset += $secs;
}