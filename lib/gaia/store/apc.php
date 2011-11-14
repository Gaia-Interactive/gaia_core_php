<?php
namespace Gaia\Store;
use Gaia\Time;

/**
* make APC conform to our cache interface. Works pretty well except for the replace call, since apc
* doesn't exactly support that. I can fake it though.
*/
class Apc implements Iface {
    
    protected $core = FALSE; 
    
    public function __construct(){
        if( ! function_exists('apc_fetch') ) {
            $this->core = new Prefix( new Mock, __CLASS__);
        }
    }
    public function get( $request){
        if( $this->core ) return $this->core->get( $request );
        $res = apc_fetch( $request );
        if( is_array( $request ) && ! is_array( $res ) ) $res = array();
        if( $res === FALSE ) return NULL;
        return $res;
    }
    
    public function set($k, $v, $expires = 0 ){
        if( $v === NULL ) return $this->delete( $k );
        if( $this->core ) return $this->core->set( $k, $v, $expires );
        if( $expires > Wrap::TTL_30_DAYS ) $expires -= Time::now();
        return apc_store( $k, $v, $expires );
    }
    
    public function add( $k, $v, $expires = 0 ){
        if( $expires > Wrap::TTL_30_DAYS ) $expires -= Time::now();
        if( $this->core ) return $this->core->add( $k, $v, $expires );
        return apc_add( $k, $v, $expires );
    }
    
    public function replace( $k, $v, $expires = 0 ){
        if( $expires > Wrap::TTL_30_DAYS ) $expires -= Time::now();
        if( $this->core ) return $this->core->replace( $k, $v, $expires );
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v, $expires );
    }
    
    public function increment( $k, $step = 1){
        if( $this->core ) return $this->core->increment( $k, $step );
        return apc_inc($k, $step );
    }
    
    public function decrement( $k, $step = 1){
        if( $this->core ) return $this->core->decrement( $k, $step );
        return apc_dec( $k, $step );
    }
    
    public function delete( $k ){
        if( $this->core ) return $this->core->delete( $k );
        apc_delete( $k );
        return TRUE;
    }
    
    public function flush(){
        if( $this->core ) return $this->core->flush();
        return apc_clear_cache('user');
    }
    
    public function ttlEnabled(){
        if( $this->core ) return $this->core->ttlEnabled();
        return TRUE;
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }
    
    public function __set( $k, $v ){
        return $this->set( $k, $v );
    }
    public function __get( $k ){
        return $this->get( $k );
    }
    public function __unset( $k ){
        return $this->delete( $k );
    }
    public function __isset( $k ){
        $v = $this->get( $k );
        if( $v === FALSE || $v === NULL ) return FALSE;
        return TRUE;
    } 
}