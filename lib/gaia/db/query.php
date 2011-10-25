<?php
namespace Gaia\DB;

class Query {
    
    public static function format($query, array $args, $escape = NULL) {
        if( ! $args || count( $args ) < 1 ) return $query;
        if( ! $escape ) $escape = function( $v ) { return "'" . addslashes( $v ) . "'"; };
        $query = preg_replace('/([\s\(,])\?/', '${1}%s', $query );
        return preg_replace_callback(
            '/%([sif%])/',
            function ($matches) use (&$args, $escape) {
                if ($matches[1] == '%') {
                    return '%';
                }
                $arg = array_shift($args);
                $modify_funcs = array(
                    's' => $escape,
                    'i' => function($v) { $v = strval($v); return preg_match('/^-?[1-9]([0-9]+)?$/', $v ) ? $v : 0; },
                    'f' => function($v) {  $v = strval($v); return preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $v ) ? $v : 0; }
                );
                $match = array_pop($matches);
                if( ! $match || ! isset( $modify_funcs[$match] ) ) $match = 's';
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