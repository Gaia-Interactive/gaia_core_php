<?php
namespace Gaia\DB;
use Gaia\Exception;
 
class Connection {

    protected static $map = array();
    protected static $instances = array();
    protected static $version = '__EMPTY__';
    
    public static function load( array $conf ){
        foreach( $conf as $name => $cb ){
            if( ! is_callable( $cb ) ) continue;
            self::$map[ $name ] = $cb;
        }
        self::$version =  md5( print_r(self::$map, TRUE ) );
    }
    
    public static function instance( $name ){
        if( isset( self::$instances[ $name ] ) ) return self::$instances[ $name ];
        return self::$instances[ $name ] = self::get( $name );
    }
    
    public static function get( $name ){
        if( ! isset( self::$map[ $name ] ) ) throw new Exception('invalid config', $name );
        $db = call_user_func( self::$map[ $name ] );
        if( ! $db instanceof Iface ) throw new Exception('invalid db layer', $name );
        return $db;
    }
    
    public static function remove( $name ){
        if( is_scalar( $name ) ) {
            unset( self::$instances[ $name ] );
            return;
        }
        
        foreach( self::$instances as $k => $v ){
            if( $v === $name ) unset( self::$instances[ $k ] );
        }
    }
    
    public static function add( $name, $db, $force = FALSE ){
        if( $force || ! isset( self::$instances[ $name ] ) ) self::$instances[ $name ] = $db;
        return $db;
    }
    
    public static function version(){
        return self::$version;
    }
    
    public static function config( $name ){
        if( ! isset( self::$map[ $name ] ) ) return FALSE;
        $params = parse_url( self::$map[ $name] );
        if( ! is_array( $params ) ) return FALSE;
        if( ! isset( $params['scheme'] ) ) return FALSE;
        return $params;

    }
}
