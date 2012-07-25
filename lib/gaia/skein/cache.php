<?php
namespace Gaia\Skein;
use Gaia\Store;

class Cache extends Wrap {

    protected $core;
    protected $cache;
    protected $ttl;

    public function __construct( Iface $core, \Gaia\Store\Iface $cache, $ttl = NULL ){
        $this->core = $core;
        $this->cache = $cache;
        $this->ttl = $ttl;
    }
    
    public function count(){
        return Util::count( $this->shardSequences() );
    }
    
    public function get( $id ){
        if( is_array( $id ) ) return $this->multiget( $id );
        $res = $this->multiget( array( $id ) );
        return isset( $res[ $id ] ) ? $res[ $id ] : NULL;
    }
    
    protected function multiGet( array $ids ){
        // add the callback handler for populating missing rows from core.
        $options = array(
            'callback'=>array($this->core, 'get'),
            'missing'=>TRUE,
            'method'=>'add',
            'timeout'=>$this->ttl,
        );
        
        $cache = new Store\Callback( $this->cache, $options );
        return $cache->get( $ids );
    }
    
    
    public function add( $data, $shard = NULL ){
        $id = $this->core->add( $data, $shard );
        $this->cache->set( $id, $data );
        list( $shard, $sequence ) = Util::parseId( $id );
        $shardkey = 'shard_' . $shard;
        $this->cache->set($shardkey, $sequence, $this->ttl );
        
        $shards = $this->cache->get('shards');
        if( ! is_array( $shards ) ) return $id;
        
        if( ! isset( $shards[ $shard ] ) ) {
            $shards[ $shard ] = 1;
            $this->cache->set('shards', $shards, $this->ttl );
        }
        
        return $id;
    }
    
    public function store( $id, $data ){
        $this->core->store( $id, $data );
        $this->cache->set( $id, $data, $this->ttl );
        return TRUE;
    }
    
    public function ids( array $params = array() ){
        return Util::ids( $this->shardSequences(), $params );
    }
    
    public function filter( array $params ){
        Util::filter( $this, $params );
    }
    
    public function shardSequences(){
        $shard_sequences = NULL;
        $core = $this->core;
        
        $shards = $this->cache->get('shards');
        
        if( ! is_array( $shards ) ) {
            $shard_sequences = $core->shardSequences();
            $shards = array_fill_keys(array_keys($shard_sequences), 1);
            $this->cache->set('shards', $shards);
        }
        
        if( count( $shards ) < 1 ) return array();
        
        $shard_keys = array();
        
        foreach( array_keys( $shards ) as $shard ){
            $shard_keys[  'shard_' . $shard ] = $shard;
        }
        $result = array();
        foreach( $this->cache->get( array_keys( $shard_keys ) ) as $shard_key => $sequence ){
            $sequence = strval($sequence );
            if( ! ctype_digit( $sequence ) ) continue;
            $result[ $shard_keys[ $shard_key ] ] = $sequence;
        }
        
        foreach( $shard_keys as $shard_key=>$shard ){
            if( isset( $result[ $shard ] ) ) continue;
            if( ! isset( $shard_sequences ) )  $shard_sequences = $core->shardSequences();
            if( ! isset( $shard_sequences[ $shard ] ) ) continue;
            $this->cache->add( $shard_key, $shard_sequences[ $shard ], $this->ttl);
            $result[ $shard ] = $shard_sequences[ $shard ];
        }
        
        return $result;
    }
}
