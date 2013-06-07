<?php
namespace Gaia\Identifier;
use Gaia\Store;

class Cache extends Wrap {
    
    protected $cache;
    protected $revision;
    protected $ttl;
    
    public function __construct( Iface $core, Store\Iface $cache, $ttl = NULL ){
        parent::__construct( $core );
        $this->cache = $cache;
        $this->ttl = $ttl;
    }
    
        
    public function byId( $request ){
        $res = parent::byId( $request );
        return $res;
    }
    
    public function byName( $request  ){
        $res = parent::byName( $request );
        return $res;
    }
    
    public function delete( $id, $name ){
        $res = parent::delete( $id, $name );
        return $res;
    }
    
    public function store( $id, $name, $strict = FALSE ){
        $res = parent::store( $id, $name, $strict );
        return $res;
    }
    
    
}