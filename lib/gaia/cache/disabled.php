<?php
namespace Gaia\Cache;

class Disabled extends Base { 
    function __construct(){}
    function decrement($key, $value = 1){return FALSE;}
    function flush(){ return FALSE; }
    function delete($key) { return FALSE; }
    function get($key, $options = NULL){ if( is_array( $key ) ) return array(); return FALSE; }
    function increment($key, $value = 1){ return FALSE; }
    function replace($key, $value, $flag = NULL, $expire = NULL){ return FALSE; }
    function set($key, $value, $flag = NULL, $expire = NULL){ return FALSE; }
    function add($key, $value, $flag = NULL, $expire = NULL){ return FALSE; }
    function queue($keys, $options = NULL ){ }
    function fetchAll(){ return array(); }
    function __call($method, $args){ return FALSE; }
}
