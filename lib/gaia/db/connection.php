<?php
namespace Gaia\DB;
use Gaia\Exception;
 
class Connection {

    protected static $map = array();
    protected static $instances = array();
    protected static $version = '__EMPTY__';
    
    public static function load( array $conf , $version = NULL){
        foreach( $conf as $name => $cb ){
            if( ! is_callable( $cb ) ) continue;
            self::$map[ $name ] = $cb;
        }
        self::$version = $version ? md5( $version ) : md5( var_export(self::$map, TRUE ) );
    }
    
    public static function instance( $name ){
        if( isset( self::$instances[ $name ] ) ) return self::$instances[ $name ];
        return self::$instances[ $name ] = self::get( $name );
    }
    
    public static function instances(){
        return self::$instances;
    }
    
    public static function reset(){
        self::$instances = array();
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
            while( $v instanceof \Gaia\DB && $v->core() instanceof Iface ){
                $v = $v->core();
                if( $v === $name ) unset( self::$instances[ $k ] );
            }
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
        return ( isset( self::$map[ $name ] ) ) ? self::$map[ $name ] : FALSE;
    }
}
