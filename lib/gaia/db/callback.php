<?php
namespace Gaia\DB;

class Callback implements IFace {

    protected $callbacks = array();
    
    function __construct( array $callbacks = NULL ){
        if( is_array( $callbacks ) ) $this->callbacks = $callbacks;
    }
    
    public function begin($auth = NULL){
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
        array_shift($args);
        return $this->format_query_args( $query, $args );
    }

    public function format_query_args($query, array $args) {
         if( ! isset( $this->callbacks[ __FUNCTION__ ] ) ) return Query::format( $query, $args );
         return $this->__call( __FUNCTION__, array( $query, $args ) );
    }
    
    public function __get( $k ){
        return $this->__call( __FUNCTION__, array( $k ) );
    }

    public function __set( $k, $v ){
        return $this->__call( __FUNCTION__, array( $k, $v ) );
    }
    
    public function __isset( $k ){
        return $this->__call( __FUNCTION__, array( $k ) );
    }
    
    public function __unset( $k ){
        return $this->__call( __FUNCTION__, array( $k ) );
    }
    
    public function __toString(){
        if( isset( $this->callbacks[ __FUNCTION__ ] ) ) return $this->__call( __FUNCTION__, array() );
        return '(Gaia\DB\Callback object)';
    }
    
    public function __call( $method, array $args ){
        if( isset( $this->callbacks[ $method ] ) ) {
            return call_user_func_array($this->callbacks[ $method ], $args );
        }
        return FALSE;
    }
}