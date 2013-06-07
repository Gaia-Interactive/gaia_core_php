<?php
namespace Gaia\Identifier;
use Gaia\Store;
use Gaia\DB\Transaction;

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
        $core = $this->core;
        $cb = function( $request )use( $core ){
            return $core->byId( $request );
        };
        
        $options = array(
            'callback'=>$cb,
            'timeout'=> $this->ttl,
            'cache_missing' => TRUE,
            'method' => 'add',
        );
        $cacher = new Store\Callback( $this->cacher('id'), $options);
        return $cacher->get( $request );
    }
    
    public function byName( $request  ){
        $core = $this->core;
        $cb = function( $request )use( $core ){
            return $core->byName( $request );
        };
        
        $options = array(
            'callback'=>$cb,
            'timeout'=> $this->ttl,
            'cache_missing' => TRUE,
            'method' => 'add',
        );
        $cacher = new Store\Callback( $this->cacher('name'), $options);
        return $cacher->get( $request );
    }
    
    public function delete( $id, $name ){
        $res = parent::delete( $id, $name );
        $this->clearCache($id, $name);
        return $res;
    }
    
    public function store( $id, $name, $strict = FALSE ){
        Transaction::onRollback(array($this, 'clearCache'), array($id, $name ));
        
        
        $namecheck = $this->cacher('id')->get( $id );
        $idcheck = $this->cacher('name')->get($name);
        
        $res = parent::store( $id, $name, $strict );
        $this->cacher('id')->set($id, $name, $this->ttl);
        $this->cacher('name')->set($name, $id, $this->ttl);
        
        if( $namecheck != $name && $namecheck !== null ){
            $this->cacher('name')->delete( $namecheck );
        }
        
        if( $id != $idcheck && $idcheck !== null ){
            $this->cacher('id')->delete( $idcheck );
        }
        
        return $res;
    }
    
    public function cacher( $prefix ){
        return new Store\Prefix( $this->cache, '/' . $prefix . '/' );
    }
    
    public function clearCache( $id, $name ){
        if( strlen( $id ) > 0 ) $this->cacher('id')->delete($id);
        if( strlen( $name ) > 0 ) $this->cacher('name')->delete($name);
        return TRUE;
    }
}