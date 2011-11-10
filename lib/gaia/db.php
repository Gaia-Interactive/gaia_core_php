<?php
namespace Gaia;
use Gaia\DB\Transaction;
use Gaia\DB\Iface;

class DB implements Iface {
    
    protected $core;
    protected $callbacks = array();
    protected $_lock = FALSE;
    protected $_txn = FALSE;
    
    function __construct( $core ){
    
        while( $core instanceof \Gaia\DB && $core->core() instanceof Iface ) $core = $core->core();

        $this->core = $core;
        /*
        if( $core instanceof Iface ){
            trigger_error('invalid db object', E_USER_ERROR);
            //exit(1);
        }
        */
        if( $core instanceof \PDO ){            
           $this->callbacks = include __DIR__ . '/db/adapter/pdo.php';
        } elseif( $core instanceof \MySQLi ) {     
           $this->callbacks = include __DIR__ . '/db/adapter/mysqli.php';
        } elseif( $core instanceof \CI_DB_driver) {
           $this->callbacks = include __DIR__ . '/db/adapter/ci.php';
        } else {
            trigger_error('invalid db object', E_USER_ERROR);
            exit(1);
        }
    }
    
    public function core(){
        return $this->core;
    }
    
    public function start($auth = NULL){
        if( $this->core instanceof Iface ) return $this->core->start( $auth );
        if( $auth == Transaction::SIGNATURE){
            if( $this->lock ) return FALSE;
            $this->txn = TRUE;
            $f = $this->callbacks[ __FUNCTION__];
            return (bool) $f($auth);
        }
        Transaction::start();
        if( ! Transaction::add($this) ) return FALSE;
        return TRUE;
    }
    
    public function rollback($auth = NULL){
        if( $this->core instanceof Iface ) return $this->core->rollback( $auth );
        if( $auth != Transaction::SIGNATURE) return Transaction::rollback();
        if( ! $this->txn ) return FALSE;
        if( $this->lock ) return TRUE;
        $f = $this->callbacks[ __FUNCTION__];
        $res = (bool) $f($auth);
        $this->lock = TRUE;
        return $res;
    }
    
    public function commit($auth = NULL){
        if( $this->core instanceof Iface ) return $this->core->commit( $auth );
        if( $auth != Transaction::SIGNATURE) return Transaction::commit();
        if( ! $this->txn ) return FALSE;
        if( $this->lock ) return FALSE;
        $f = $this->callbacks[ __FUNCTION__];
        $res = (bool) $f($auth);
        if( ! $res ) return $res;
        $this->txn = FALSE;
        return $res;
    }
    
    public function execute($query){
        if( $this->lock ) return FALSE;
        $args = func_get_args();
        array_shift( $args );
        $sql = $this->format_query_args( $query, $args );
        $f = $this->callbacks[ __FUNCTION__ ];
        $res = $f( $sql );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function error(){
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f();
    }
    
    public function errorcode(){
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f();
    }
    
    public function format_query( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->format_query_args( $query, $args );
    }

    public function format_query_args($query, array $args) {
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f( $query, $args );
    }
    
    public function isa( $name ){
        if( $this instanceof $name ) return TRUE;
        if( $this->core instanceof $name ) return TRUE;
        $f = $this->callbacks[ 'isa' ];
        return $f( $name );
    }
    
    public function __tostring(){
        $f = $this->callbacks[ __FUNCTION__ ];
        return $f();
    }
    
    public function __get( $k ){
        if( $this->core instanceof Iface) return $this->core->__get( $k );
        if( $k == 'lock' ) return $this->_lock;
        if( $k == 'txn' ) return $this->_txn;
    }

    public function __set( $k, $v ){
        if( $this->core instanceof Iface) return $this->core->__set( $k, $v );
        if( $k == 'lock' ) return $this->_lock = (bool) $v;
        if( $k == 'txn' ) return $this->_txn = (bool) $v;
    }
 
}