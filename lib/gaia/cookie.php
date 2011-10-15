<?php
namespace Gaia;
use Gaia\Container;
use Gaia\Exception;

class Cookie {

    protected $config;
    
    public function __construct( $config = NULL ){
        $config = $config instanceof Container ? $config : new Container( $config );
        if( ! isset( $config->prefix ) ) $config->prefix = get_class( $this );
        if( ! isset( $config->path ) ) $config->path = '/';
        $this->config = $config;
    }
    
    public function get( $k ){
        $key = $this->config->prefix . $k;
        if( ! isset( $_COOKIE[ $key ] ) ) return NULL;
        return $_COOKIE[ $key ];
    }
    
    public function set( $k, $v ){
        if( headers_sent() ) throw new Exception('headers sent, could not store');
        $c = $this->config;
        $key = $c->prefix . $k;
        if( $v !== NULL) {
            setcookie($key, $_COOKIE[ $key ] = $v, $c->expires, $c->path, $c->domain, $c->secure, $c->httponly);
        } else {
            unset( $_COOKIE[ $key ] );
            setcookie($key, '', 0, $c->path, $c->domain, $c->secure, $c->httponly);
        }
        return $v;
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
}