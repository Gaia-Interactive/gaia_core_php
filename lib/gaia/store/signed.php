<?php
namespace Gaia\Store;
use \Gaia\Serialize\SignBase64;
class Signed extends Wrap {

    private $s;
    
    public function __construct( Iface $core, $secret ){
        $this->s = new SignBase64( $secret );
        parent::__construct( $core );
    }
    
    public function get( $k ){
        if( is_array( $k ) ){
            $res = array();
            foreach( $this->core->get($k) as $_k => $v){
                $v = $this->s->unserialize( $v );
                if( $v !== FALSE && $v !== NULL ) $res[ $_k ] = $v;
            }
            return $res;
        }
        return $this->s->unserialize( $this->core->get( $k ) );
    }
    
    public function set( $k, $v ){
        $this->core->set( $k, $this->s->serialize( $v ) );
        return $v;
    }

    public function add( $k, $v, $expires = NULL ){
        return $this->core->add( $k, $this->s->serialize($v), $expires );
    }
    
    public function replace( $k, $v, $expires = NULL ){
        return $this->core->replace( $k, $this->s->serialize($v), $expires );
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
}