<?php
namespace Gaia\Store;

class KVP implements Iface {
 /**
    * internal data storage
    */
    protected $__d = array();
    
    public function __construct( $input = NULL ){
        $this->load( $input );
    }
    
    public function set($name, $value){
        return $this->__d[ $name ] = $value;
    }
    
    public function increment($name, $value = 1) {
        if(! isset($this->__d[$name]) ) $this->__d[$name] = 0;
        return $this->__d[$name] += $value;
    }
    
    public function decrement($name, $value = 1) {
        if(! isset($this->__d[$name]) ) $this->__d[$name] = 0;
        return $this->__d[$name] -= $value;
    }
    
    public function add( $name, $value, $ttl = 0 ){
        if( $this->__isset( $name ) ) return FALSE;
        return $this->set( $name, $value, $ttl );
    }
    
    public function replace( $name, $value, $ttl = 0 ){
        if( ! $this->__isset( $name ) ) return FALSE;
        return $this->set( $name, $value, $ttl );
    }
    
    public function get($name){
        if( is_array( $name ) ){
            $res = array();
            foreach( $name as $_k ){
                $v = $this->__get( $_k );
                if( $v === NULL ) continue;
                $res[ $_k ] = $v;
            }
            return $res;
        }
        if( ! is_scalar( $name ) ) return NULL;
        return isset( $this->__d[ $name ] ) ? $this->__d[ $name ] : NULL;

    }
    
    public function delete($name){
        unset( $this->__d[ $name ] );
        return TRUE;
    }
    
    public function flush(){
        $this->__d = array();
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
        // all done.
    }
    
    public function ttlEnabled(){
        return FALSE;
    }

    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __set( $k, $v ){
        return $this->set( $k, $v );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __get( $k ){
        return $this->get( $k );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __unset( $k ){
        $this->delete( $k );
    }
    
   /**
    * @see http://www.php.net/oop5.magic
    */
    public function __isset( $k ){
        return ( $this->get( $k ) !== NULL ) ? TRUE  : FALSE;
    }
     
   /**
    * if we try to print the object, give something easier to scan.
    */
    public function __toString(){
        $out = get_class( $this ) . " {\n";
        foreach( $this->__d as $k=>$v ){
            if( ! is_scalar( $v ) ) $v = print_r( $v, TRUE);
            if( ( $len = strlen( $v ) ) > 100 ) $v = substr($v, 0, 100) . '... (' . $len . ')';
            $v = str_replace("\n", '\n',  str_replace("\r", '\r', $v));
            $out .= '    [' . $k . '] => ' . $v . "\n";
        }
        $out .= "}\n";
        return $out;
    }
}
