<?php
namespace Stratum;
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
        $key = sha1( strtolower( serialize( $params ) ) );
        $res = $this->cacher->get( $key );
        if( is_array( $res ) ) return $res;
        $res = $this->core->query( $params );
        $this->cacher->set( $key, $res, $this->ttl );
        return $res;
    }
}
