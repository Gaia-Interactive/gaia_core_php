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
        $db = $stockpile->inTran() ? Transaction::instance( $dsn ) : Connection::instance( $dsn );
        switch( get_class( $db ) ){
            case 'Gaia\DB\Driver\MySQLi': 
                        $classname = 'Gaia\Stockpile\Storage\MySQLi\\' . $name;
                        break;
            
            case 'Gaia\DB\Driver\PDO': 
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
                        
                        $classname = 'Gaia\Stockpile\Storage\\' . $driver . '\\' . $name;
                        break;
            default:
                throw new Exception('invalid db driver', $db );


        }
        return new $classname( $db, $stockpile->app(), $stockpile->user(), $dsn . '.' . Connection::version() );
    }
}

