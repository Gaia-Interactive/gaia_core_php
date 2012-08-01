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
    
    public function search( array $identifiers ){
        //return $this->core->search( $identifiers );
        $cache = new Cacher\Prefix( $this->cache, 'affiliate_identifiers/');
        $cache = new Cacher\Callback( $cache, array(
            'callback'=> array( $this->core, 'search'),
            'timeout'=>300,
            'cache_missing'=>TRUE,
        ));
        $res = $cache->get( $identifiers );
        //var_dump( $res );
        return $res;
    }
        
    public function get( array $affiliate_ids ){
        //return $this->core->get( $affiliate_ids );
        $cache = new Cacher\Prefix( $this->cache, 'affiliate_ids/');
        $cache = new Cacher\Callback( $cache, array(
            'callback'=> array( $this->core, 'get'),
            'timeout'=>300,
            'cache_missing'=>TRUE,
        ));
        $res = $cache->get( $affiliate_ids );
        //var_dump( $res );
        return $res;
    }
    
    public function findRelated( array $identifiers ){
        return Util::findRelated( $this, $identifiers );
    }
    
    public function join( array $identifiers ){
        return $this->joinRelated( $this->findRelated($identifiers) );
    }
    
    public function joinRelated( array $related ){
        $cache = new Cacher\Prefix( $this->cache, 'affiliate_ids/');

        $ids = array();
        $cache_identifiers  = new Cacher\Prefix( $this->cache, 'affiliate_identifiers/');
        foreach( $related as $identifier => $affiliate_id ){
            if( $affiliate_id === NULL ) continue;
            $cache_identifiers->delete($identifier);
            $ids[ $affiliate_id ] = 1;
        }
        if( $ids ) {
            foreach( $ids as $id => $set ){
                $cache->delete( $id );
            }
        }
        $res =  $this->core->joinRelated( $related );
        $cache_result = array();
        foreach( $res as $identifier => $affiliate_id ){
            if( ! isset( $cache_result[ $affiliate_id ] ) ) $cache_result[ $affiliate_id ] = array();
            $cache_result[ $affiliate_id ][] = $identifier;
            $cache_identifiers->set( $identifier, $affiliate_id );
        }
        foreach( $cache_result as $affiliate_id => $identifiers ){
            $cache->set( $affiliate_id, $identifiers, 300 );
        }
        return $res;
        
    }
    
    public function delete( array $identifiers ){
        $res = $this->search( $identifiers );
        $affiliate_ids = array_unique( array_values( $res ) );
        $cache = new Cacher\Prefix( $this->cache, 'affiliate_ids/');
        foreach( $affiliate_ids as $affiliate_id ){
            $cache->delete( $affiliate_id );
        }
        $cache = new Cacher\Prefix( $this->cache, 'affiliate_identifiers/');
        foreach( $res as $identifier => $affiliate_id ){
            $cache->delete( $identifier );
        }
        return $this->core->delete( $identifiers );
    }
}
