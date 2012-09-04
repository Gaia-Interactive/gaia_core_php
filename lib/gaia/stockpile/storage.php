<?php
namespace Gaia\Stockpile;
use \Gaia\Exception;
use \Gaia\DB\Transaction;
use \Gaia\DB\Connection;
use Gaia\Store;

class Storage {

     protected static $resolver;
     protected static $autoschema = FALSE;
     
         // hash the user id against vbuckets and determine wich database name to use.
    public static function attach( $callback ){
        if( ! is_callable( $callback ) ) throw new Exception('invalid connection resolver', $callback );
        self::$resolver = $callback;
    }
    
    
    /***    INTERNAL METHODS BELOW      ***/
     
     public static function get( Iface $stockpile, $name ){
        if( ! isset( self::$resolver ) ) throw new Exception('need to invoke ' . __CLASS__ . '::attach( $callback ) first');
        $res = call_user_func( self::$resolver, $stockpile, $name );
        if( $res instanceof Storage\Iface ) return $res;
        return self::loadDefault( $stockpile, $name, $res );
     }
     
     public static function enableAutoSchema( $bool = TRUE ){
        self::$autoschema = (bool) $bool;
     }

     public static function isAutoSchemaEnabled(){
        return self::$autoschema;
     }
    protected static function loadDefault( Iface $stockpile, $name, $dsn ){
        $db = Connection::instance( $dsn );
        if( ! $db instanceof \Gaia\DB ) $db = new \Gaia\DB( $db );
        if( $db->isa('mysql') ) {
            $classname = 'Gaia\Stockpile\Storage\MySQL\\' . $name;
        } elseif( $db->isa('sqlite') ){
            $classname = 'Gaia\Stockpile\Storage\SQLite\\' . $name;
        } else {
            throw new Exception('invalid db driver', $db );
        }
        $table = $stockpile->app() . '_stockpile_' . constant($classname . '::TABLE');
        $object = new $classname( $db, $table, $stockpile->user() );
        if( ! \Gaia\Stockpile\Storage::isAutoSchemaEnabled() ) return $object;
        $cache = new Store\Gate( new Store\Apc() );
        $key = 'stockpile/storage/__create/' . md5( $dsn . '/' .  Connection::version() . '/' . $table . '/' . $classname );
        if( $cache->get( $key ) ) return $object;
        if( ! $cache->add( $key, 1, 60 ) ) return $object;
        $object->create();
        return $object;
    }
}

