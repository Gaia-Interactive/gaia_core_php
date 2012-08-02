<?php
namespace Gaia\DB\Driver;
use Gaia\DB\Connection;
use Gaia\DB\Transaction;

class CI implements \Gaia\DB\Iface {
    
    protected $core;
    
    
    protected $lock = FALSE;
    protected $txn = FALSE;
    
    public function __construct( $core ){
        $this->core = $core;
    }

    
    public function execute( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->query( $this->prep_args( $query, $args ) );
    }
    
	public function query($sql, $binds = FALSE, $return_object = TRUE){
        if( $this->lock ) return FALSE;
        $res = $this->core->query( $sql, $binds, $return_object );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function simple_query( $query ){
        if( $this->lock ) return FALSE;
        $res = $this->core->simple_query( $query );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function close(){
        Connection::remove( $this );
        if( $this->lock ) return FALSE;
        $rs = $this->core->close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function start( $auth = NULL ){
        return $this->trans_start($auth);
    }
    
    public function trans_start($auth = NULL){
        if( $auth == Transaction::SIGNATURE ) {
            if( $this->lock ) return FALSE;
            $this->txn = TRUE;
            $this->core->trans_start();
            return TRUE;
        }
        Transaction::start();
        if( ! Transaction::add($this) ) return FALSE;
        return TRUE;
    }
    
    public function rollback($auth = NULL){
        return $this->trans_rollback($auth);
    }
    
    public function trans_rollback($auth = NULL){
        if( $auth != Transaction::SIGNATURE ) return Transaction::rollback();
        if( ! $this->txn ) return $this->core->trans_rollback(); 
        if( $this->lock ) return TRUE;
        $rs = $this->core->trans_rollback();
        $this->close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit($auth=NULL){
        return $this->trans_complete($auth);
    }
    
    public function trans_complete($auth = NULL){
        if( $auth != Transaction::SIGNATURE ) return Transaction::commit();
        if( ! $this->txn ) return $this->core->trans_complete(); 
        if( $this->lock ) return FALSE;
        $res =  $this->core->trans_complete();
        if( ! $res ) return $res;
        $this->txn = FALSE;
        return $res;
    }
    
    public function prep( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->prep_args( $query, $args );
    }

    public function prep_args($query, array $args) {
        if( ! $args || count( $args ) < 1 ) return $query;
        $conn = $this;
        return \Gaia\DB\Query::prepare( 
            $query, 
            $args, 
            function($v) use( $conn ){ return "'" . $conn->escape_str( $v ) . "'"; }
            );
    }
    
    public function isa( $name ){
        if( $this instanceof $name ) return TRUE;
        if( $this->core instanceof $name ) return TRUE;
        if( $this->core->dbdriver == $name ) return TRUE;
        return FALSE;
    }
    
    public function hash(){
        return spl_object_hash( $this->core );
    }
    
    public function __toString(){
        @ $res = print_r( $this, TRUE);
        return $res;
    }
    
    public function __get( $k ){
        if( $k == 'lock' ) return $this->lock;
        if( $k == 'txn' ) return $this->txn;
        return $this->core->$k;
    }
    
    public function __set( $k, $v ){
        if( $k == 'lock' ) return $this->lock = (bool) $v;
        if( $k == 'txn' ) return $this->txn = (bool) $v;
        return $this->core->$k = $v;
    }
    
    public function __isset( $k ){
        return isset( $this->core->$k );
    }
    
    public function __call( $method, $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }

}
