<?php
namespace Gaia\ShortCircuit;

class Resolver {

    const UNDEF = '__UNDEF__';

    public static function search( $name, $type ){
        $dir =  Router::appdir();
        $key = md5( __CLASS__ . '/' . __FUNCTION__ . '/' . $dir . '/' . $type . '/' . $name);
        $n = function_exists('apc_fetch') ? apc_fetch( $key ) : FALSE;
        if( $n == self::UNDEF ) return '';
        if( $n ) return $n;
        $args = explode('/', $name );
        do{
            $n = implode('/', $args );
            if( strlen($n) < 1 ) break;
            $res =self::get( $n, $type);
            if( ! $res ) continue;
            apc_store( $key, $n, 300);
            return $n;
        } while( array_pop( $args ) );
        if( function_exists('apc_store') ) apc_store($key, self::UNDEF, 30);
        return '';
    }
    
    public static function get($name, $type ) {
        $name = strtolower($name);
        $type = strtolower($type);
        if( strlen( $name ) < 1 || strpos($name, '.') !== FALSE ) return FALSE;
        $dir =  Router::appdir();
        $key = md5( __CLASS__ . '/' . __FUNCTION__ . '/' . $dir . '/' . $type . '/' . $name);
        $path =  function_exists('apc_fetch') ? apc_fetch( $key ) : FALSE;
        if( $path == self::UNDEF ) return '';
        if( $path ) return $path;
        $path = $dir . $name . '.' . $type . '.php';
        if( ! file_exists( $path ) ) {
            $path = $dir . $name . '/index.' . $type . '.php';
            if( ! file_exists( $path ) ) $path = '';
        }
        if( function_exists('apc_store') ) apc_store( $key, ($path ? $path : self::UNDEF), (! $path ? 300 : 60) );
        return $path;
    }
}