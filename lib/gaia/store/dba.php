<?php
namespace Gaia\Store;
use Gaia\Serialize;
use Gaia\Exception;

/**
* make APC conform to our cache interface. Works pretty well except for the replace call, since apc
* doesn't exactly support that. I can fake it though.
*/
class DBA implements Iface {
    
    protected $handle; 
    protected $s;
    
    public function __construct($handle, Serialize\Iface $s = NULL ){
        if( is_resource( $handle ) ){
            if( get_resource_type( $handle ) != 'dba' ) throw new Exception('invalid handle');
            $this->handle = $handle;
        } else {
            $file = $handle;
            if( ! file_exists( $file ) && ! touch( $file ) ) throw new Exception('invalid file');
            $this->handle = dba_open( $file, 'cd' );
            if( ! $this->handle ) throw new Exception('invalid handle');
        }
        
        if( ! $s instanceof Serialize\Iface ) $s = new Serialize\PHP();
        $this->s = $s;
    }
    public function get( $request){
        if( is_array( $request ) ){
            $result = array();
            foreach( $request as $k ){
                $v = $this->get( $k );
                if( $v === NULL ) continue;
                $result[ $k ] = $v;
            }
            return $result;
        }
        $v = dba_fetch($request, $this->handle);
        if( $v === NULL || $v === FALSE ) return NULL;
        return $this->s->unserialize($v);
    }
    
    public function set($k, $v ){
         return @dba_replace( $k, $this->s->serialize($v), $this->handle );
    }
    
    public function add( $k, $v ){
        if( dba_exists($k, $this->handle ) ) return FALSE;
        return @dba_insert( $k, $this->s->serialize($v), $this->handle );
    }
    
    public function replace( $k, $v ){
        if( ! dba_exists($k, $this->handle ) ) return FALSE;
        return $this->set( $k, $v );
    }
    
    public function increment( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === NULL ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        $v += $step;
        if( ! $this->replace( $k, $v ) ) return FALSE;
        return $v;
    }
    
    public function decrement( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === NULL ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        $v -= $step;
        if( ! $this->replace( $k, $v ) ) return FALSE;
        return $v;
    }
    
    public function delete( $k ){
        return @dba_delete( $k,  $this->handle );
    }
    
    public function flush(){
        return FALSE;
    }
    
    public function ttlEnabled(){
        return FALSE;
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->set( $k, $v);
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
        return dba_exists($k, $this->handle);
    }
    
    public function __destruct(){
        if( $this->handle ) dba_close( $this->handle );
    }
}