<?php
namespace Gaia\Stratum;
use Gaia\Store;

class Cache implements Iface {
    
    protected $cacher;
    protected $core;
    protected $rev;
    
    const REVKEY = "\0.rev";
    
    public function __construct( Iface $core, Store\Iface $cacher, $ttl = NULL ){
        $this->core = $core;
        $this->cacher = $cacher;
        $this->ttl = $ttl;
    }
    
    protected function rev(){
        if( $this->rev ) return $this->rev;
        $this->rev = $this->cacher->get(self::REVKEY);
        if( $this->rev ) return $this->rev;
        return $this->newRev();
    }
    
    protected function newRev(){
        $this->cacher->set(self::REVKEY, $this->rev = time() .'.' . mt_rand(0, 100000000));
        return $this->rev;
    }
    
    public function store( $constraint, $stratum ){
        $response = $this->core->store( $constraint, $stratum );
        $this->newRev();
        return $response;
    }
    
    public function query( array $params = array() ){
        $refresh =  isset( $params['refresh'] ) && $params['refresh'] ? TRUE : FALSE;
        unset( $params['refresh']);
        $params['rev'] = ( $refresh ) ? $this->newRev() : $this->rev();
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
