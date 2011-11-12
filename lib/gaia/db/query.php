<?php
namespace Gaia\DB;

class Query {
    
    public static function prepare($query, array $args, $escape = NULL) {
        if( ! $args || count( $args ) < 1 ) return $query;
        if( ! $escape ) $escape = function( $v ) { return "'" . addslashes( $v ) . "'"; };
        $modify_funcs = array(
                    '%s' => $escape,
                    '%i' => function($v) { $v = strval($v); return preg_match('/^-?[1-9]([0-9]+)?$/', $v ) ? $v : 0; },
                    '%f' => function($v) {  $v = strval($v); return preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $v ) ? $v : 0; }
                );
                
                
        foreach( array_keys( $args ) as $k ){
            if( is_int( $k ) ) continue;
            $modify_funcs[ preg_quote( ':' . $k ) ] = $escape;
        }
        
        $query = preg_replace('/([\s\(,\=\>\<])\?/', '${1}%s', $query );
        $pattern = '/(%%|'. implode('|', array_keys( $modify_funcs )). ')/';
        return preg_replace_callback(
            $pattern,
            function ($matches) use (&$args, $modify_funcs) {
                //var_dump( $matches );
                if ($matches[0] == '%%') {
                    return '%';
                }
                $arg = array_shift($args);
                $match = $matches[0];
                if( ! $match || ! isset( $modify_funcs[$match] ) ) $match = '%s';
                if ($arg instanceof Traversable) {
                    $arg = iterator_to_array($arg);
                    $arg = array_map($modify_funcs[$match], $arg);
                    return implode(', ', $arg);
                } elseif (is_array($arg)) {
                    $arg = array_map($modify_funcs[$match], $arg);
                    return implode(', ', $arg);
                } else {
                    $func = $modify_funcs[$match];
                    return $func($arg);
                }
            },
            $query
        );
    }
}