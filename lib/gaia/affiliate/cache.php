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
        
    public function identifiers( array $affiliate_ids ){
        $cache = $this->affiliationCache();
        $cache = new Cacher\Callback( $cache, array(
            'callback'=> array( $this->core, __FUNCTION__),
            'timeout'=>300,
            'cache_missing'=>TRUE,
        ));
        $res = $cache->get( $affiliate_ids );
        return $res;
    }
    
    public function findRelated( array $identifiers ){
        return Util::findRelated( $this, $identifiers );
    }
    
    public function join( array $identifiers ){
        return $this->joinRelated( $this->findRelated($identifiers) );
    }
    
    public function joinRelated( array $related ){
        $a_cache = $this->affiliationCache();
        $i_cache  = $this->identifierCache();

        $ids = array();
        foreach( $related as $identifier => $affiliate_id ){
            if( $affiliate_id === NULL ) continue;
            $i_cache->delete($identifier);
            $ids[ $affiliate_id ] = 1;
        }
        if( $ids ) {
            foreach( $ids as $id => $set ){
                $a_cache->delete( $id );
            }
        }
        $res =  $this->core->joinRelated( $related );
        $cache_result = array();
        foreach( $res as $identifier => $affiliate_id ){
            if( ! isset( $cache_result[ $affiliate_id ] ) ) $cache_result[ $affiliate_id ] = array();
            $cache_result[ $affiliate_id ][] = $identifier;
            $i_cache->set( $identifier, $affiliate_id );
        }
        foreach( $cache_result as $affiliate_id => $identifiers ){
            $a_cache->set( $affiliate_id, $identifiers, 300 );
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
