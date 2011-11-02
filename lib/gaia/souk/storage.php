<?php
namespace Gaia\Souk;
use Gaia\Exception;
use Gaia\DB\Transaction;
use Gaia\DB\Connection;
use Gaia\Store;

class Storage {

     protected static $resolver;
     protected static $autoschema = FALSE;
     protected static $cacher;
     
         // hash the user id against vbuckets and determine wich database name to use.
    public static function attach( $callback ){
        if( ! is_callable( $callback ) ) throw new Exception('invalid connection resolver', $callback );
        self::$resolver = $callback;
    }
    
    public static function cacher( \Gaia\Store\Iface $cacher = NULL ){
        if( $cacher !== NULL ) self::$cacher = $cacher;
        if( ! isset( self::$cacher ) ) {
            self::$cacher = new Store\Tier(new Store\Gate( new Store\Apc ), new Store\KVP );
        }
        return self::$cacher;
    }
    
    
    /***    INTERNAL METHODS BELOW      ***/
     
     public static function get( Iface $souk ){
        if( ! isset( self::$resolver ) ) throw new Exception('need to invoke ' . __CLASS__ . '::attach( $callback ) first');
        $res = call_user_func( self::$resolver, $souk );
        if( $res instanceof Storage\Iface ) return $res;
        return self::loadDefault( $souk, $res );
     }
     
     public static function enableAutoSchema( $bool = TRUE ){
        self::$autoschema = (bool) $bool;
     }

     public static function isAutoSchemaEnabled(){
        return self::$autoschema;
     }
    protected static function loadDefault( Iface $souk, $dsn ){
        $db = Connection::instance( $dsn );
        if( ! $db instanceof \Gaia\DB\Iface ) throw new Exception('invalid db driver', $db );
        if( $db->isa('mysqli') ){
            $classname = 'Gaia\Souk\Storage\MySQLi';
        } elseif( $db->isa('pdo') ){
            switch( $db->getAttribute(\PDO::ATTR_DRIVER_NAME) ){
                case 'mysql': 
                    $driver = 'MyPDO';
                    break;
                
                case 'sqlite':
                    $driver = 'LitePDO';
                    break;
                
                default:
                    throw new Exception('invalid db driver', $db );

            }
            $classname = 'Gaia\Souk\Storage\\' . $driver;
        } else {
            throw new Exception('invalid db driver', $db );
        }
        return new $classname( $db, $souk->app(), $souk->user(), $dsn . '.' . Connection::version() );
    }
}

