<?php
namespace Gaia\DB;

class Mock implements IFace {

    protected $callback;
    
    function __construct( $callback = NULL ){
        if( is_callable( $callback ) ) $this->callback = $callback;
    }
    
    public function begin(){
        if( $this->callback ) {
            $args = func_get_args();
            return call_user_func( $this->callback, __FUNCTION__, $args );
        }
        return TRUE;
    }
    
    public function rollback(){
        if( $this->callback ) {
            $args = func_get_args();
            return call_user_func( $this->callback, __FUNCTION__, $args );
        }
        return TRUE;
    }
    
    public function commit(){
        if( $this->callback ) {
            $args = func_get_args();
            return call_user_func( $this->callback, __FUNCTION__, $args );
        }
        return TRUE;
    }
    
    public function execute($query){
        if( $this->callback ) {
            $args = func_get_args();
            return call_user_func( $this->callback, __FUNCTION__, $args );
        }
        return FALSE;
    }
    
    public function format_query( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->format_query_args( $query, $args );
    }

    public function format_query_args($query, array $args) {
        if( ! $args || count( $args ) < 1 ) return $query;
        $modify_funcs = array(
            's' => function($v) { return "'".addslashes($v)."'"; },
            'i' => function($v) { $v = strval($v); return preg_match('/^-?[1-9]([0-9]+)?$/', $v ) ? $v : 0; },
            'f' => function($v) {  $v = strval($v); return preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $v ) ? $v : 0; }
        );
    
        return preg_replace_callback(
            '/%([sif%])/',
            function ($matches) use (&$args, $modify_funcs) {
                if ($matches[1] == '%') {
                    return '%';
                }
                if (!count($args)) {
                    throw new Exception("Missing values!");
                }
                $arg = array_shift($args);
    
                if ($arg instanceof Traversable) {
                    $arg = iterator_to_array($arg);
                    $arg = array_map($modify_funcs[$matches[1]], $arg);
                    return implode(', ', $arg);
                } elseif (is_array($arg)) {
                    $arg = array_map($modify_funcs[$matches[1]], $arg);
                    return implode(', ', $arg);
                } else {
                    $func = $modify_funcs[$matches[1]];
                    return $func($arg);
                }
    
    
            },
            $query
        );
    }
}