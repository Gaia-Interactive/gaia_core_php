<?php
namespace Gaia\Stockpile;
use \Gaia\Exception;
use \Gaia\DB\Transaction;
use \Gaia\DB\Connection;
use \Gaia\Cache;

class Storage {

     protected static $resolver;
     
         // hash the user id against vbuckets and determine wich database name to use.
    public function attach( $callback ){
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

    protected static function loadDefault( Iface $stockpile, $name, $dsn ){
        $db = $stockpile->inTran() ? Transaction::instance( $dsn ) : Connection::instance( $dsn );
        switch( get_class( $db ) ){
            case 'Gaia\DB\Driver\MySQLi': 
                        $classname = 'Gaia\Stockpile\Storage\MySQLi\\' . $name;
                        break;
            
            case 'Gaia\DB\Driver\PDO': 
                        switch( $db->driver() ){
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
        
        $storage = new $classname( $db, $stockpile->app(), $stockpile->user() );
        $cache = new Cache\Gate( new Cache\Apc() );
        $key = 'st/t/' . $dsn . '/' . $stockpile->app() . '/' . $name . '/' . Connection::version();
        if( $cache->get( $key ) ) return $storage;
        if( ! $cache->add( $key, 1, 60 ) ) return $storage;
        $storage->create();
        return $storage;
    }
}

