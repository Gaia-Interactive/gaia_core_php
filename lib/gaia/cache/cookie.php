<?php
namespace Gaia\Cache;
use Gaia\SignedCookie;

/**
* make APC conform to our cache interface. Works pretty well except for the replace call, since apc
* doesn't exactly support that. I can fake it though.
*/
class Cookie Implements Iface {
    
    protected $signedcookie;
    
    public function __construct( $v = NULL ){
        $v = $v instanceof SignedCookie ? $v : new SignedCookie( $v );
        $this->signedcookie = $v;
    }
    
    public function flush(){
        foreach( $this->signedcookie->keys() as $key ){
            $this->clearCookie($key);
        }
    }
    
    function set( $k, $v, $expires = 0 ){
        if( $v === FALSE ) return FALSE;
        if( $expires < 1 ){
            $expires = 0;
        } else {
            $expires = self::time() + $expires;
        }
        $this->setCookie($k, array( $v, $expires ));
        return TRUE;
    }
    
    function add( $k, $v, $expires = 0 ){
        $res = $this->get( $k );
        if( $res !== FALSE ) return FALSE;
        $res = $this->set( $k, $v, $expires );
        return $res;
    }
    
    function replace( $k, $v, $expires = 0 ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v, $expires );
    }
    
    function increment( $k, $step = 1){
        $result = $this->getCookie($k);
        if( $result === NULL ) return FALSE;
        $v = $result[0];
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        $res = $result[0] = bcadd( $v, $step );
        $this->setCookie( $k, $result );
        return $res;
    }
    
    function decrement( $k, $step = 1){
        $result = $this->getCookie($k);
        if( $result === NULL ) return FALSE;
        $v = $result[0];
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        $res = $result[0] = bcsub( $v, $step );
        $this->setCookie( $k, $result );
        return $res;
    }
    
    function delete( $k ){
        $this->clearCookie( $k );
        return TRUE;
    }
    
    public function get( $key ){
        if( is_array( $key ) ) return $this->getMulti( $key );
        $v = $this->getCookie( $key );
        if( ! $v ) return FALSE;
        return $v[0];
    }
    
    protected function getMulti( array $keys ){
        $res = array();
        foreach( $keys as $key ){
            $v = $this->get( $key );
            if( $v === FALSE ) continue;
            $res[ $key ] = $v;
        }
        return $res;
    }
    
    protected function getCookie( $k ){
        $value = $this->signedcookie->get( $k );
        if( ! is_array( $value ) || count( $value ) != 2 ) return $this->clearCookie( $k );
        $expires = $value[1];
        if( $expires == 0 ) return $value;
        if( $expires < self::time() ) {
            return $this->clearCookie( $k );
        }
        return $value;
    }
    
    protected function setCookie( $k, $v ){
        return $this->signedcookie->set( $k, $v );
    }
    
    protected function clearCookie( $k ){
        return $this->signedcookie->set( $k, NULL);
    }
    
    protected function time(){
        return time() + Mock::$time_offset;
    }
}