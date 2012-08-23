<?php
namespace Gaia\Stratum;
use Gaia\Store;

class Cache implements Iface {
    
    protected $cacher;
    protected $core;
    
    public function __construct( Iface $core, Store\Iface $cacher, $ttl = NULL ){
        $this->core = $core;
        $this->cacher = $cacher;
        $this->ttl = $ttl;
    }
    
    public function store( $constraint, $stratum ){
        return $this->core->store( $constraint, $stratum );
    }
    
    public function query( array $params = array() ){
        $refresh =  isset( $params['refresh'] ) && $params['refresh'] ? TRUE : FALSE;
        unset( $params['refresh']);
        $key = sha1( strtolower( serialize( $params ) ) );
        $res =  ! $refresh ? $this->cacher->get( $key ) : NULL;
        if( is_array( $res ) ) return $res;
        $res = $this->core->query( $params );
        $this->cacher->set( $key, $res, $this->ttl );
        return $res;
    }
    
    public function delete( $constraint ){
        return $this->core->delete( $constraint );
    }

}
