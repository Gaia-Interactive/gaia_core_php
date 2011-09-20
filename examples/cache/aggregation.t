#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

// @see https://github.com/gaiaops/gaia_core_php/wiki/cache-aggregation

class AnimalFarm {
    // only caching for a short time for demo purposes, so you can see it refresh.
    const CACHE_TIMEOUT = 10;
    
    public static function all(){
        $key = 'all_ids';
        $cacher = self::cache();
        $ids = $cacher->get($key);
        if( ! is_array( $ids ) ){
            $ids = self::_allIdsFromDB();
            $cacher->set($key, $ids, self::CACHE_TIMEOUT);
        }
        return self::get( $ids );
    }
    
    public static function get( array $ids ){
        $options = array(
            'callback'=>array(__CLASS__, '_FromDB'),
            'timeout'=> self::CACHE_TIMEOUT,
            'cache_missing' => TRUE,
            'method' => 'set',
        );
        $cache = new Cache\Callback( self::cache(), $options );
        return $cache->get( $ids );
    }
    
    /*
    * fake data, from a query like:
    * SELECT id from animals WHERE type = 'farm';
    */
    static function _allIdsFromDB(){
        return array(1,2,3,4,5);
    }
    
    /*
    * fake data returned from running a query like:
    * SELECT id, name FROM animals WHERE id IN( ?, ?, ? ....);
    * and return the list of names keyed by id.
    */
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
        return new Cache\Prefix( Connection::memcache(), __CLASS__ . '/' );
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
        );
        $cache = new Cache\Callback( self::cache(), $options );
        return $cache->get( $ids );
    }
    
    /*
    * fake data, for a query like:
    * SELECT * FROM animalfoods WHERE animal_id IN (?, ... );
    * key the result set by animal id, with a list of food ids for each.
    */
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
        return  new Cache\Prefix( Connection::memcache(), __CLASS__ . '/');
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
        );
        $cache = new Cache\Callback( self::cache(), $options );
        return $cache->get( $ids );
    }
    
    /*
    * faking the query from the db:
    *    SELECT id, name from foods where id IN(? ... );
    */
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
        return new Cache\Prefix( Connection::memcache(), __CLASS__ . '/');
    }
    
    

}

Tap::plan(4);

$animals = AnimalFarm::all();

Tap::is($animals, array(1=>'horse', 2 => 'pig', 3 => 'chicken', 4 => 'sheep', 5 => 'turkey'), 'got back my animals');

$foodlist = AnimalFoodTracker::get( array_keys( $animals ) );
Tap::is( $foodlist, array( 1=> array(1,3), 2 => array(2), 3 => array(3), 4 => array(1), 5 => array(3)), 'got my foodlist');

$food_ids = array();

foreach( $foodlist as $animal_id => $foods ){
    foreach( $foods as $food_id ){
        if( ! in_array( $food_id, $food_ids ) ) $food_ids[] = $food_id;
    }
}

$foods = AnimalFood::get( $food_ids );

Tap::is( $foods, array(1=>'hay', 3=>'grain', 2=>'slop'), 'got the food names based on the ids');

$messages = array();

foreach( $animals as $id => $name ){
    $eat = array();
    foreach( $foodlist[ $id ] as $food_id ) $eat[] = $foods[ $food_id ];
    $messages[] = "$name eats " . implode(', ', $eat );
}

Tap::is( $messages, array(
    'horse eats hay, grain',
    'pig eats slop',
    'chicken eats grain',
    'sheep eats hay',
    'turkey eats grain',
    ), 'can tell what each animal eats now');

Tap::debug($messages);