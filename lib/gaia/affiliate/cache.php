<?php
namespace Gaia\Affiliate;
use Gaia\Store as Cacher;
use Gaia\Exception;

class Cache implements Iface {

    protected $core;
    protected $cache;

    public function __construct( Iface $core, Cacher\Iface $cache ){
        $this->core = $core;
        $this->cache = $cache;
    }
    
    public function affiliations( array $identifiers ){
        $cache = $this->identifierCache();
        $cache = new Cacher\Callback( $cache, array(
            'callback'=> array( $this->core, __FUNCTION__),
            'timeout'=>300,
            'cache_missing'=>TRUE,
        ));
        $res = $cache->get( $identifiers );
        return $res;
    }
        
    public function identifiers( array $affiliations ){
        $cache = $this->affiliationCache();
        $cache = new Cacher\Callback( $cache, array(
            'callback'=> array( $this->core, __FUNCTION__),
            'timeout'=>300,
            'cache_missing'=>TRUE,
        ));
        $res = $cache->get( $affiliations );
        return $res;
    }
    
    public function related( array $identifiers ){
        return Util::related( $this, $identifiers );
    }
    
    public function join( array $identifiers ){
        return $this->_joinRelated( $this->related($identifiers) );
    }
    
    public function _joinRelated( array $related ){
        $a_cache = $this->affiliationCache();
        $i_cache  = $this->identifierCache();

        $ids = array();
        foreach( $related as $identifier => $affiliation ){
            if( $affiliation === NULL ) continue;
            $i_cache->delete($identifier);
            $ids[ $affiliation ] = 1;
        }
        if( $ids ) {
            foreach( $ids as $id => $set ){
                $a_cache->delete( $id );
            }
        }
        $res =  $this->core->_joinRelated( $related );
        $cache_result = array();
        foreach( $res as $identifier => $affiliation ){
            if( ! isset( $cache_result[ $affiliation ] ) ) $cache_result[ $affiliation ] = array();
            $cache_result[ $affiliation ][] = $identifier;
            $i_cache->set( $identifier, $affiliation );
        }
        foreach( $cache_result as $affiliation => $identifiers ){
            $a_cache->set( $affiliation, $identifiers, 300 );
        }
        return $res;
        
    }
    
    public function delete( array $identifiers ){
        $res = $this->affiliations( $identifiers );
        $affiliations = array_unique( array_values( $res ) );
        $cache = $this->affiliationCache();
        foreach( $affiliations as $affiliation ){
            $cache->delete( $affiliation );
        }
        $cache = $this->identifierCache();
        foreach( $res as $identifier => $affiliation ){
            $cache->delete( $identifier );
        }
        return $this->core->delete( $identifiers );
    }
    
    protected function identifierCache(){
        return new Cacher\Prefix( $this->cache, 'identifiers/');
    }
    
    protected function affiliationCache(){
        return new Cacher\Prefix( $this->cache, 'affiliations/');

    }
}
