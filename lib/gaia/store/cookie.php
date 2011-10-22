<?php
namespace Gaia\Store;
use Gaia\Exception;

class Cookie implements Iface {

    protected $config;
    
    public function __construct( Iface $config = NULL ){
        $config = $config instanceof Iface ? $config : new Container( $config );
        if( ! isset( $config->prefix ) ) $config->prefix = md5(get_class( $this ));
        if( ! isset( $config->path ) ) $config->path = '/';
        $this->config = $config;
    }
    
    public function get( $k ){
        if( is_array( $k ) ){
            $res = array();
            foreach( $k as $_k ){
                $v = $this->get( $_k );
                if( $v !== FALSE && $v !== NULL ) $res[ $_k ] = $v;
            }
            return $res;
        }
        $key = $this->config->prefix . $k;
        if( ! isset( $_COOKIE[ $key ] ) ) return FALSE;
        return $_COOKIE[ $key ];
    }
    
    public function set( $k, $v ){
        if( headers_sent() ) throw new Exception('headers sent, could not store');
        $c = $this->config;
        $key = $c->prefix . $k;
        if( $v !== NULL) {
            setcookie($key, $_COOKIE[ $key ] = $v, $c->ttl, $c->path, $c->domain, $c->secure, $c->httponly);
        } else {
            unset( $_COOKIE[ $key ] );
            setcookie($key, '', 0, $c->path, $c->domain, $c->secure, $c->httponly);
        }
        return $v;
    }
    
    public function delete( $k ){
        $this->set( $k, NULL);
        return TRUE;
    }
    
    public function keys(){
        $prefix = $this->config->prefix;
        $len = strlen( $prefix );
        $keys = array();
        foreach( array_keys($_COOKIE ) as $k ){
            if( substr( $k, 0, $len) == $prefix) {
                $keys[] = substr($k, $len + 1);
            }
        }
        return $keys;
    }
    
    public function flush(){
        return FALSE;
    }
        
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }

    public function add( $k, $v ){
        $res = $this->get( $k );
        if( $res !== FALSE && $res !== NULL) return FALSE;
        $res = $this->set( $k, $v );
        return $res;
    }
    
    public function replace( $k, $v ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v );
    }
    
    public function increment( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return $this->set( $k, bcadd( $v, $step ));
    }
    
    public function decrement( $k, $step = 1){
        $v = $this->get( $k );
        if( $v === FALSE ) return FALSE;
        $v = strval( $v );
        if( ! ctype_digit( $v ) ) return FALSE;
        return $this->set( $k, bcsub( $v, $step ));
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
    
    public function supportsTTL(){
        return FALSE;
    }
}