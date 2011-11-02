<?php
namespace Gaia\Souk;
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

