#!/usr/bin/env php
<?php
namespace Demo;
include_once __DIR__ . '/../common.php';

use Gaia\Shard\VBucket;
use Gaia\Test\Tap;

class UserDSN {
    
    const CONNECTION_PREFIX = 'user';
    protected static $vb;
    
    // what database do we need to connect to for a given user id?
    public static function get( $id ){
        return self::CONNECTION_PREFIX . self::vb()->shard( $id );
    }
    
    // set up the table if it doesn't exist. only needs to be done once.
    public static function initialize(){
        //$db->execute('CREATE TABLE `user_vbuckets` (`vbucket` INT UNSIGNED NOT NULL PRIMARY KEY, `shard` INT UNSIGNED NOT NULL) ENGINE=InnoDB');
        $vb = new VBucket( range(1, 3) );
        foreach( $vb->export() as $vbucket => $shard ){
            //$rs = $db->execute('INSERT IGNORE INTO `user_vbuckets` (`vbucket`, `shard`) VALUES (%i, %i)', $vbucket, $shard );
        }
        //self::cache()->set('vbucket', $vb, 60);
    }
    
    protected static function vb(){
        if( isset( self::$vb ) ) return self::$vb;
        //$cache =self::cache();
        //$vb = $cache->get('vbucket');
        if( $vb instanceof VBucket ) return self::$vb = $vb;
        $vb = new VBucket( range(1, 3) );
        //$rs = $db->execute( 'SELECT `vbucket`, `shard` FROM `user_vbuckets`' );
        //$list = array();
        //while( $row = $rs->fetch_array() ) $list[ $row['vbucket'] ] = $row['shard'];
        //$vb = new VBucket( $list );
        //$cache->set('vbucket', $vb, 60);
        return self::$vb = $vb;
    }
}

Tap::plan(2);
Tap::is( UserDSN::get(3), 'user2', 'user id 3 maps to correct shard');
Tap::is( UserDSN::get(5555), 'user1', 'user id 5555 maps to correct shard');
