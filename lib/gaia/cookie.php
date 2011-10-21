<?php
namespace Gaia;
use Gaia\Container;
use Gaia\Exception;
use Gaia\StorageIface as Iface;

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
    
    public function set( $k, $v, $expires = NULL ){
        if( headers_sent() ) throw new Exception('headers sent, could not store');
        $c = $this->config;
        $key = $c->prefix . $k;
        if( $v !== NULL) {
            if( $expires === NULL ) $expires = $c->expires;
            setcookie($key, $_COOKIE[ $key ] = $v, $expires, $c->path, $c->domain, $c->secure, $c->httponly);
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
    
        
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }

    public function add( $k, $v, $expires = NULL ){
        $res = $this->get( $k );
        if( $res !== FALSE && $res !== NULL) return FALSE;
        $res = $this->set( $k, $v, $expires );
        return $res;
    }
    
    public function replace( $k, $v, $expires = NULL ){
        if( ! $this->get( $k ) ) return FALSE;
        return $this->set( $k, $v, $expires );
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
}