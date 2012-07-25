<?php
namespace Gaia\Skein;

class Wrap implements Iface {

    protected $core;
    
    public function __construct( Iface $core ){
        $this->core = $core;
    }
    
    public function add( $data, $shard = NULL){
        return $this->core->add( $data, $shard );
    }
    
    public function store( $id, $data ){
        return $this->core->store($id, $data );
    }
    
    public function count(){
        return $this->core->count();
    }
    
    public function get( $id ){
        return $this->core->get( $id );
    }
    
    public function ids( array $params = array() ){
        return Util::ids( $this->shardSequences(), $params );
    }
    
    public function shardSequences(){
        return $this->core->shardSequences();
    }
    
    public function filter( array $params ){
        Util::filter( $this, $params );
    }
    
    public function __call( $method, array $args ){
        return call_user_func_array( array($this->core, $method), $args );
    }
    
}

