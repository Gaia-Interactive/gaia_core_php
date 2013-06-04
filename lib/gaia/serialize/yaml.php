<?php
namespace Gaia\Serialize;
use sfYaml;

class YAML implements Iface {

    protected $prefix;
    protected $len = 0;
    
    public function __construct( $prefix = '#__YAML__:' ){
        $this->prefix = $prefix;
        $this->len = strlen( $this->prefix );
    }

    public function serialize($v){
        $v = json_decode(json_encode($v), TRUE);
        if( ! $this->len ) return sfYaml::dump( $v );
        if( is_bool($v) || ! is_scalar( $v ) ) return $this->prefix . sfYaml::dump( $v );
        return $v;
    }
    
    public function unserialize( $v ){
        if( $v === NULL ) return NULL;
        if( ! is_scalar( $v ) ) return $v;
        if( $this->len < 1 ) return sfYaml::load( $v );
        if( substr( $v, 0, $this->len) != $this->prefix) return $v;
        return sfYaml::load(substr( $v, $this->len) );
    }
}