<?php
namespace Gaia\EnumPath;
use Gaia\Store;

class Cache extends Wrap {
    
    protected $cache;
    protected $revision;
    protected $ttl;
    
    public function __construct( Iface $core, Store\Iface $cache, $ttl = NULL ){
        parent::__construct( $core );
        $this->revision = new Store\Revision( $cache );
        $this->cache = $cache;
        $this->ttl = NULL;
    }
    
    public function spawn( $parent = NULL ){
        $id = parent::spawn( $parent );
        $this->bustCache();
        return $id;
    }
    
    public function alter( $id, $parent ){
        $res = parent::alter( $id, $parent );
        $this->bustCache();
        $this->cache()->set( $id, $res, $this->ttl );
        return $res;
    }
    
    public function idsInPath( $path ){
        $cache = $this->cache();
        $res = $cache->get( $path );
        if( is_array( $res )) return $res;
        $res = parent::idsInPath( $path );
        $cache->set( $path, $res, $this->ttl );
        return $res;
    }

    public function pathById( $input ){
        $core = $this->core;
        $cache = new Store\Callback( $this->cache(), array(
            'callback'=>function( $input )use( $core ){ return $core->pathById( $input ); },
            'timeout'=>$this->ttl,
            'cache_missing'=>TRUE,
        ));
        return $cache->get( $input );
        
    }
    
    protected function bustCache(){
        $this->revision->get('__REV', TRUE );
    }
    
    protected function cache(){
        return new Store\Prefix( $this->cache, $this->revision->get('__REV') . '_' );
    }
    
    protected function cacheKeyByPath( $path, $refresh = FALSE ){
        return $this->revision()->get('__REV', $refresh ) . '_' . $path;
    }
}