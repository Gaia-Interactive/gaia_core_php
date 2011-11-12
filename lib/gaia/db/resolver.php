<?php
namespace Gaia\DB;
 
class Resolver {

    protected static $map = array();
    
    public static function load( array $list ){
        foreach( $list as $alias => $name )self::$map[ $alias ] = $name;
    }

    public static function get( $name ){
        return isset( self::$map[ $name ] ) ? self::$map[ $name ] : $name;
    }
}
