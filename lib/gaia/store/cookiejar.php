<?php
namespace Gaia\Store;
use Gaia\Exception;
use Gaia\Serialize;
use Gaia\Container;

class CookieJar extends Wrap implements Iface {

    protected $config;
    protected $ob = FALSE;
    protected $checksum = NULL;
    
    public function __construct( $config = NULL ){
        if( headers_sent() ) throw new Exception('headers sent, cannot store');
        $cookiejar = $this;
        ob_start(function( $output )use( $cookiejar ){ $cookiejar->__write(); return $output;  } );
        $this->ob = TRUE;
        $config = $config instanceof Iface ? $config : new KVP( $config );
        if( ! isset( $config->name ) ) $config->name = md5(get_class( $this ));
        if( ! isset( $config->path ) ) $config->path = '/';
        if( ! $config->serializer instanceof Serialize\Iface ){
            if( ! $config->secret ) throw new Exception('no secret specified', $config);
            $config->serializer = new Serialize\SignBase64($config->secret);
        }
        $this->config = $config;
        $key = $config->name;
        $v = isset( $_COOKIE[ $key ] ) ? $_COOKIE[ $key ] : NULL;
        $this->checksum = sha1( $v );
        $data = $config->serializer->unserialize($v);
        parent::__construct( new Container( $data ) );
    }
    
    public function add( $key, $value, $expires = 0){
        $res = $this->core->add( $key, $value, $expires );
        if( ! $res ) return $res;
        $this->__save();
        return $res;
    }
    
    public function set( $key, $value, $expires = 0){
        $res = $this->core->set( $key, $value, $expires );
        if( ! $res ) return $res;
        $this->__save();
        return $res;
    }
    
    public function replace( $key, $value, $expires = 0){
        $res = $this->core->replace( $key, $value, $expires );
        if( ! $res ) return $res;
        $this->__save();
        return $res;
    }
    
    public function increment( $key, $value = 1 ){
        $res = $this->core->increment( $key, $value );
        if( ! $res ) return $res;
        $this->__save();
        return $res;
    }
    
    public function decrement( $key, $value = 1 ){
        $res = $this->core->decrement( $key, $value );
        if( ! $res ) return $res;
        $this->__save();
        return $res;
    }
    
    public function delete( $key ){
        $res = $this->core->delete( $key );
        if( ! $res ) return $res;
        $this->__save();
        return $res;
    }
    
    public function flush(){
        $res = $this->core->flush();
        $this->__save();
        return $res;
    }
    
    public function __destruct(){
        if( $this->ob ) {
            ob_end_flush();
        }
        $this->__write();

    }
    
    public function __write(){
        if( ! $this->ob ) return;
        $this->ob = FALSE;
        $c = $this->config;
        if( headers_sent() ) throw new Exception('headers sent, could not store');
        $v = $c->serializer->serialize($this->all());
        $key = $c->name;
        if( sha1( $v ) == $this->checksum ) return;
        if( $v !== NULL) {
            setcookie($key, $_COOKIE[ $key ] = $v, $c->ttl, $c->path, $c->domain, $c->secure, $c->httponly);
        } else {
            unset( $_COOKIE[ $key ] );
            setcookie($key, '', 0, $c->path, $c->domain, $c->secure, $c->httponly);
        }
        return $v;
    }
    
    public function __save(){
        $_COOKIE[ $this->config->name ] = $this->config->serializer->serialize($this->all());;
    }
    
    public function ttlEnabled(){
        return FALSE;
    }
}