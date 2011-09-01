<?php
namespace Gaia\Stockpile;
use \Gaia\Exception;

class ConnectionResolver {

    protected static $resolver;

    public static function get( Iface $obj ){
        if( ! isset( self::$resolver ) ) throw new Exception('need to invoke ' . __CLASS__ . '::attach( $callback ) first');
        return call_user_func( self::$resolver, $obj );
    }
    
    // hash the user id against vbuckets and determine wich database name to use.
    public function attach( $callback ){
        if( ! is_callable( $callback ) ) throw new Exception('invalid connection resolver', $callback );
        self::$resolver = $callback;
    }
    
}