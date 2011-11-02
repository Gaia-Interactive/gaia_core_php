<?php

namespace Gaia\DB;

class Wrapper implements IFace {
    
    protected $core;
    
    public function __construct( Iface $core ){
        $this->core = $core;
    }
    
    public function start($auth = NULL){
        return $this->core->start($auth = NULL);
    }
    
    public function rollback($auth = NULL){
        return $this->core->rollback($auth = NULL);
    }
    
    public function commit($auth = NULL){
        return $this->core->commit($auth = NULL);
    }
    
    public function execute($query){
        $args = func_get_args();
        return call_user_func_array( array($this->core, 'execute'), $args );
    }
    
    public function format_query($query){
        return $this->core->format_query( $query );
    }
    
    public function format_query_args( $query, array $args ){
        return $this->core->format_query_args( $query, $args );
    }
    
    public function isa( $name ){
        if( $this instanceof $name ) return TRUE;
        if( method_exists( $this->core, 'isa') ) return $this->core->isa( $name );
        return ( $this->core instanceof $name );
    }
    
    public function __get( $k ){
        return $this->core->$k;
    }
    
    public function __set( $k, $v ){
        return $this->core->$k = $v;
    }
    
    public function __isset( $k ){
        return isset( $this->core->$k );
    }
    
    public function __call( $method, $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }

}