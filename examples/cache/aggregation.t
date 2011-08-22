#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';

/**
* One of the first things programmers learn when building a new application is how to join two 
* related data sets together using a query join. In the example below, the join for feeding my 
* animals on the farm might look something like this:
*
*    SELECT a.name as animal, f.name as food 
*           FROM animal a INNER JOIN animalfoods af ON a.id = af.animal_id
*           INNER JOIN food f ON f.id = af.food_id
*           WHERE a.type = 'farm';
*
* The query finds all of the foods eaten by an animal in a one to many relationship. The output would
* look something like:

*       horse   | hay
*       horse   | grain 
*       chicken | grain
*       sheep   | hay
*       turkey  | grain
*
* I have no problem with this query. It works efficiently on a small scale, and gets you the data
* you need. But what if you are dealing with a data set that changes frequently and is very large? 
* Caching this query would get very little cache re-use. If you use joins, you can't use a row-wise
* caching strategy. And you can't move tables to separate databases. You also can't shard your 
* data sets since all the data needs to be in the same server.
*
* We recommend breaking each query down into their individual components. This makes it easier to 
* shard data and cache it. Here's how the queries might look:
*
*   SELECT * FROM animal WHERE type = 'farm';
*   SELECT * FROM animalfoods WHERE animal_id IN (?, ... );
*   SELECT * FROM foods WHERE id IN( ?, ... );
* 
* Use your application to join the data together. This takes work off of the database server. I've 
* been to many conferences on scaling web applications and they all advise the same thing. Keep your 
* queries simple, and cache your data as much as possible. When you do joins often you are 
* duplicating elements into your cache. Most of the time your application can do the job of grabbing 
* related sets of information and joining them up more efficently than if you do a database join. 
* It shifts work away from the bottleneck point which is the database server. We can always add 
* more caching servers and webservers to the farm to increase throughput.
*
*The database server is the biggest bottleneck in your application. It is very cheap and easy to add 
* more webservers to a server farm, and more caching servers to a cluster. But a database server is 
* the single component where it is not possible to simply add more of them and scale out. To scale 
* the database, you must by faster and more expensive hardware. Using table joins requires your 
* database server to perform more of the calculations and it requires you to keep all your data on one
* database server. Scale out methodology encourages you to use commodity hardware and shift 
* responsibilities away from components where there are potential bottlenecks. In the example above,
* I can move each table to a separate database server if I need to, and I don't have to make major
* changes to my application to do it.
*/


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
        
        return self::cache()->get( $ids, $options );
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
        return new Cache\Namespaced( Connection::memcache(), __CLASS__ . '/');
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
        
        return self::cache()->get( $ids, $options );
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
        return new Cache\Namespaced( Connection::memcache(), __CLASS__ . '/');
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
        
        return self::cache()->get( $ids, $options );
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
        return new Cache\Namespaced( Connection::memcache(), __CLASS__ . '/');
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