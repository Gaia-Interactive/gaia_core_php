#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';


class AnimalFarm {
    // only caching for a short time for demo purposes, so you can see it refresh.
    const CACHE_TIMEOUT = 10;
    
    public static function all(){
        $key = 'all_ids';
        $cacher = self::cache();
        $ids = $cacher->get($key);
        if( ! is_array( $ids ) ){
            $ids = self::_allIdsFromDB();
            $cacher->set($key, $ids, MEMCACHE_COMPRESSED, self::CACHE_TIMEOUT);
        }
        return self::get( $ids );
    }
    
    public static function get( array $ids ){
        $options = array(
            'callback'=>array(__CLASS__, '_FromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
            'compression'=>MEMCACHE_COMPRESSED,
        );
        
        return self::cache()->get( $ids, $options );
    }
    
    static function _allIdsFromDB(){
        return array(1,2,3,4,5);
    }
    
    static function _FromDB( array $ids ){
        return array(
            1=>'horse',
            2 => 'pig',
            3 => 'chicken',
            4 => 'sheep',
            5 => 'turkey',
        );
    }
    
    protected static function cache(){
        return new Cache\Namespaced( Connection::cache(), __CLASS__ . '/');
    }
}

class AnimalFoodTracker {

    const CACHE_TIMEOUT = 10;

    public static function get( array $ids ){
        $options = array(
            'callback'=>array(__CLASS__, '_FromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
            'compression'=>MEMCACHE_COMPRESSED,
        );
        
        return self::cache()->get( $ids, $options );
    }
    
    static function _FromDB( array $ids ){
        return array(
            1=> array(1,3),
            2 => array(2),
            3 => array(3),
            4 => array(1),
            5 => array(3),
        );
    }
    
    protected static function cache(){
        return new Cache\Namespaced( Connection::cache(), __CLASS__ . '/');
    }
}


class AnimalFood {

    // only caching for a short time for demo purposes, so you can see it refresh.
    const CACHE_TIMEOUT = 10;
    
    public static function get( array $ids ){
        $options = array(
            'callback'=>array(__CLASS__, '_FromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
            'compression'=>MEMCACHE_COMPRESSED,
        );
        
        return self::cache()->get( $ids, $options );
    }
    
    function _fromDB( array $ids ){
        $foods = array(
            1 => 'hay',
            2 => 'slop',
            3 => 'grain',        
        );
        $list = array();
        foreach( $ids as $id ){
            $list[ $id ] = $foods[ $id ];
        }
        return $list;
    }
    
    protected static function cache(){
        return new Cache\Namespaced( Connection::cache(), __CLASS__ . '/');
    }
    
    

}


$animals = AnimalFarm::all();

$foodlist = AnimalFoodTracker::get( array_keys( $animals ) );

$food_ids = array();

foreach( $foodlist as $animal_id => $foods ){
    foreach( $foods as $food_id ){
        if( ! in_array( $food_id, $food_ids ) ) $food_ids[] = $food_id;
    }
}

$foods = AnimalFood::get( $food_ids );

foreach( $animals as $id => $name ){
    $eat = array();
    foreach( $foodlist[ $id ] as $food_id ) $eat[] = $foods[ $food_id ];
    Tap::debug( "$name eats " . implode(', ', $eat ));
}