<?php
namespace Gaia\DB;

class Callback implements IFace {

    protected $callbacks = array();
    protected $lock = FALSE;
    protected $txn = FALSE;
    
    function __construct( array $callbacks = NULL ){
        if( is_array( $callbacks ) ){
            foreach( $callbacks as $k => $v ){
                $k = strtolower( $k );
                $this->callbacks[ $k ] = $v;
            }
        }
    }
    
    public function start($auth = NULL){
        $args = func_get_args();        
        if( $auth == Transaction::SIGNATURE){
            if( $this->lock ) return FALSE;
            $this->txn = TRUE;
            return (bool) $this->__call( __FUNCTION__, array() );
        }
        Transaction::start();
        if( ! Transaction::add($this) ) return FALSE;
        return TRUE;
    }
    
    public function rollback($auth = NULL){
        if( $auth != Transaction::SIGNATURE) return Transaction::rollback();
        if( ! $this->txn ) return FALSE;
        if( $this->lock ) return TRUE;
        $rs = (bool) $this->__call( __FUNCTION__, array() );
        var_dump($rs);
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit($auth = NULL){
        $args = func_get_args();
        if( $auth != Transaction::SIGNATURE) return Transaction::commit();
        if( ! $this->txn ) return FALSE;
        if( $this->lock ) return FALSE;
        $res = (bool) $this->__call( __FUNCTION__, array() );
        if( ! $res ) return $res;
        $this->txn = FALSE;
        return $res;
    }
    
    public function execute($query){
        if( $this->lock ) return FALSE;
        $args = func_get_args();
        $res = $this->__call( __FUNCTION__, $args );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function prep( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->prep_args( $query, $args );
    }

    public function prep_args($query, array $args) {
        $f = strtolower( __FUNCTION__);
        if( ! isset( $this->callbacks[ $f ] ) ) return Query::prepare( $query, $args );
        return $this->__call( $f, array( $query, $args ) );
    }
    
    public function isa( $name ){
        if( $this instanceof $name ) return TRUE;
        return $this->__call( __FUNCTION__, array( $name ) );
    }
    
    public function hash(){
        $f = strtolower( __FUNCTION__);
        if( isset( $this->callbacks[ $f ] ) ) return $this->__call( $f, array() );
        return spl_object_hash( $this );
    }
    
    public function __get( $k ){
        if( $k == 'lock' ) return $this->lock;
        if( $k == 'txn' ) return $this->txn;
        return $this->__call( __FUNCTION__, array( $k ) );
    }

    public function __set( $k, $v ){
        if( $k == 'lock' ) return $this->lock = (bool) $v;
        if( $k == 'txn' ) return $this->txn = (bool) $v;
        return $this->__call( __FUNCTION__, array( $k, $v ) );
    }
    
    public function __isset( $k ){
        return $this->__call( __FUNCTION__, array( $k ) );
    }
    
    public function __unset( $k ){
        return $this->__call( __FUNCTION__, array( $k ) );
    }
    
    public function __toString(){
        $f = strtolower( __FUNCTION__);
        if( isset( $this->callbacks[ $f ] ) ) return $this->__call( $f, array() );
        return '(Gaia\DB\Callback object)';
    }
    
    public function __call( $method, array $args ){
        $method = strtolower( $method );
        if( isset( $this->callbacks[ $method ] ) ) {
            return call_user_func_array($this->callbacks[ $method ], $args );
        }
        if( isset( $this->callbacks['__call'] ) ) return call_user_func(  $this->callbacks['__call'], $method, $args );
    }
}