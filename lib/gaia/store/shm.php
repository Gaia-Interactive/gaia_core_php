<?php
namespace Gaia\Store;
use Gaia\Exception;

/**
* make APC conform to our cache interface. Works pretty well except for the replace call, since apc
* doesn't exactly support that. I can fake it though.
*/
class Shm implements Iface {
    
    protected $id; 
    
    public function __construct($file){
        if( ! file_exists( $file ) && ! touch( $file ) ) throw new Exception('invalid file');
        $this->id = shm_attach(ftok($file, 'a'));
        if( ! $this->id ) throw new Exception('invalid file');
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
        $v = shm_get_var($this->id);
        return $v;
    }
    
    public function set($k, $v ){
        return shm_put_var( $this->id, $k, $v );
    }
    
    public function add( $k, $v ){
        if( $this->get( $k ) === NULL ) return FALSE;
        return $this->set( $k, $v );
    }
    
    public function replace( $k, $v ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v );
    }
    
    public function increment( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === NULL ) $v = 0;
        $v += $step;
        return $this->set($k, $v );
    }
    
    public function decrement( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === NULL ) $v = 0;
        $v -= $step;
        return $this->set($k, $v );
    }
    
    public function delete( $k ){
        return shm_remove_var( $this->id, $k );
    }
    
    public function flush(){
        shm_remove( $this->id );
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
        $v = $this->get( $k );
        if( $v === FALSE || $v === NULL ) return FALSE;
        return TRUE;
    }
    
    public function __destruct(){
        if( $this->id ) shm_detach( $this->id );
    }
}