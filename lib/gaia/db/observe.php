<?php
namespace Gaia\DB;

class Observe implements IFace {

    protected $callbacks = array();
    protected $db;
    
    function __construct( Iface $db,  array $callbacks = NULL ){
        $this->db = $db;
        if( $callbacks) $this->callbacks = $callbacks;
    }
    
    public function start($auth = NULL){
        $args = func_get_args();
        return $this->__call( __FUNCTION__, $args );
    }
    
    public function rollback($auth = NULL){
        $args = func_get_args();
        return $this->__call( __FUNCTION__, $args );
    }
    
    public function commit($auth = NULL){
        $args = func_get_args();
        return $this->__call( __FUNCTION__, $args );
    }
    
    public function execute($query){
        $args = func_get_args();
        return $this->__call( __FUNCTION__, $args );
    }
    
    public function format_query( $query /*, ... */ ){
        $args = func_get_args();
        return $this->__call( __FUNCTION__, $args );
    }

    public function format_query_args($query, array $args) {
        $args = func_get_args();
        return $this->__call( __FUNCTION__, $args );
    }
    
    public function isa($name) {
        if( $this instanceof $name ) return TRUE;
        return $this->__call( __FUNCTION__, array($name) );
    }
    
    public function __call( $method, $args ){
        $result = call_user_func_array( array( $this->db, $method ), $args );
        if( isset( $this->callbacks[ $method ] ) ) {
            call_user_func( $this->callbacks[$method], $args, $result );
        }
        return $result;
    }
    
    public function __toString(){
        return '{' . get_class( $this ) . '} ' . $this->db->__toString();
    }
    
    public function __get( $key ){
        return $this->db->$key;
    }
    
    public function __set( $key, $value ){
        return $this->db->$key = $value;
    }
    
    public function __isset( $key ){
        return isset( $this->db->$key );
    }
    
    public function __unset( $key ){
        unset( $this->db->$key );
    }
}