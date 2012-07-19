<?php
namespace Gaia\Skein;

class Wrap implements Iface {

    protected $core;
    
    public function __construct( Iface $core ){
        $this->core = $core;
    }
    
    public function add( $data ){
        return $this->core->add( $data );
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
    
    public function ascending( $limit = 1000, $start_after = NULL ){
        return Util::ascending( $this->shardSequences(), $limit, $start_after );
    }
    
    public function descending( $limit = 1000, $start_after = NULL ){
        return Util::descending( $this->shardSequences(), $limit, $start_after );
    }
    
    public function shardSequences(){
        return $this->core->shardSequences();
    }
    
    public function filterAscending( \Closure $c, $start_after = NULL ){
        Util::filter( $this, $c, 'ascending', $start_after );
    }
    
    public function filterDescending( \Closure $c, $start_after = NULL ){
        Util::filter( $this, $c, 'descending', $start_after );
    }
    
    public function __call( $method, array $args ){
        return call_user_func_array( array($this->core, $method), $args );
    }
    
}

