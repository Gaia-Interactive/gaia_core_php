<?php
namespace Gaia\Cache;

class Disabled Implements Iface {

    function get( $request ){
        if( is_array( $request ) ) return array();
        return FALSE;
    }
    
    function set($k, $v, $expires = 0 ){
        return FALSE;
    }
    
    function add( $k, $v, $expires = 0 ){
        return FALSE;
    }
    
    function replace( $k, $v, $expires = 0 ){
        return FALSE;
    }
    
    function increment( $k, $step = 1){
        return FALSE;
    }
    
    function decrement( $k, $step = 1){
        return FALSE;
    }
    
    function delete( $k ){
        return FALSE;
    }
}