#!/usr/bin/env php
<?php
namespace Demo;
include_once __DIR__ . '/../common.php';

use Gaia\Shard\VBucket;
use Gaia\Test\Tap;
use Gaia\Cache;
use Gaia\DB\Connection;
use Gaia\Exception;

if( ! class_exists('\MySQLi') ){
    Tap::plan('skip_all', 'php-mysqli not installed');
}


if( ! @fsockopen('127.0.0.1', '3306')) {
    Tap::plan('skip_all', 'mysql-server not running on localhost');
}

Connection::load( array(
    'main'=>function(){
            return new \Gaia\DB\Driver\MySQLi($host = '127.0.0.1', $user = NULL, $pass = NULL, $db = 'test', '3306');
        }  
    ));

class UserDSN {
    
    const CONNECTION_PREFIX = 'user';
    protected static $cache;
    protected static $vb;
    
    // what database do we need to connect to for a given user id?
    public static function get( $id ){
        return self::CONNECTION_PREFIX . self::vb()->shard( $id );
    }
    
    // set up the table if it doesn't exist. only needs to be done once.
    public static function initialize(){
        $db = Connection::instance('main');
        $rs = $db->execute('CREATE TEMPORARY TABLE `user_vbuckets` (`vbucket` INT UNSIGNED NOT NULL PRIMARY KEY, `shard` INT UNSIGNED NOT NULL) ENGINE=InnoDB');
        if( ! $rs ) throw new Exception('database error', $db );
        $vb = new VBucket( range(1, 3) );
        foreach( $vb->export() as $vbucket => $shard ){
            $rs = $db->execute('INSERT IGNORE INTO `user_vbuckets` (`vbucket`, `shard`) VALUES (%i, %i)', $vbucket, $shard );
            if( ! $rs ) throw new Exception('database error', $db );
        }
        self::cache()->set('vbucket', $vb, 60);
    }
    
    protected static function vb(){
        if( isset( self::$vb ) ) return self::$vb;
        $cache =self::cache();
        $vb = $cache->get('vbucket');
        if( $vb instanceof VBucket ) return self::$vb = $vb;
        $db = Connection::instance('main');
        $rs = $db->execute( 'SELECT `vbucket`, `shard` FROM `user_vbuckets`' );
        if( ! $rs ) throw new Exception('database error', $db );
        $list = array();
        while( $row = $rs->fetch_array() ) $list[ $row['vbucket'] ] = $row['shard'];
        $vb = new VBucket( $list );
        $cache->set('vbucket', $vb, 60);
        return self::$vb = $vb;
    }
    
    // normally the cache object core will come from a factory method, but we are hacking it in
    // here since this is a stand-alone example.
    protected static function cache(){
        if( isset( self::$cache ) ) return self::$cache;
        $core = new Cache\Memcache();
        $core->addServer('127.0.0.1', '11211');
        return self::$cache = new Cache\Gate( new Cache\Namespaced($core, __CLASS__ . '/') );
    }
}

UserDSN::initialize();
Tap::plan(2);
Tap::is( UserDSN::get(3), 'user2', 'user id 3 maps to correct shard');
Tap::is( UserDSN::get(5555), 'user1', 'user id 5555 maps to correct shard');
