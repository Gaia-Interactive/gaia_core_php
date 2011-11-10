<?php
namespace Gaia\Stockpile;
use \Gaia\Exception;
use \Gaia\DB\Transaction;
use \Gaia\DB\Connection;

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
        return new $classname( $db, $stockpile->app(), $stockpile->user(), $dsn . '.' . Connection::version() );
    }
}

