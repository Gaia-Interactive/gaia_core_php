<?php
namespace Gaia\DB\Driver;
use Gaia\DB\Connection;
use Gaia\DB\Transaction;

class PDO extends \PDO implements \Gaia\DB\Iface {


    protected $lock = FALSE;
    protected $txn = FALSE;
    protected $dsn;
    
    public function __construct( $dsn ){
        $args = func_get_args();
        call_user_func_array( array('\PDO', '__construct'), $args );
        $this->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Gaia\DB\Driver\PDOStatement', array($this)));
        $this->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT );
        $this->dsn = $dsn;
    }
    
    public function driver(){
        return $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
    
    public function dsn(){
        return $this->dsn;
    }
        
    public function execute( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        $query = $this->format_query_args( $query, $args );
        //print "#    $query\n";
        return $this->query( $query );
    }
    
    public function query( $query ){
        if( $this->lock ) return FALSE;
        $args = func_get_args();
        $res = call_user_func_array( array('\PDO', 'query'), $args);
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function locked(){
        return $this->lock;
    }
    
    public function txn(){
        return $this->txn;
    }
    
    public function exec( $query ){
        if( $this->lock ) return FALSE;
        $res = parent::exec( $query );
        if( $res ) return $res;
        if( $this->txn ) {
            Transaction::block();
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function begin( $auth = NULL ){
        return $this->beginTransaction( $auth );
    }
    
    public function beginTransaction($auth = NULL){
        if( $auth == Transaction::SIGNATURE ){
            if( $this->lock ) return FALSE;
            $this->txn = TRUE;
            return parent::beginTransaction();
        }
        Transaction::start();
        if( ! Transaction::add($this) ) return FALSE;
        return TRUE;
    }
    
    public function rollback( $auth = NULL ){
        if( $auth != Transaction::SIGNATURE ) return Transaction::rollback();
        if( ! $this->txn ) return parent::rollback(); 
        if( $this->lock ) return TRUE;
        Connection::remove( $this );
        $rs = parent::rollback();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit($auth = NULL ){
        if( $auth != Transaction::SIGNATURE ) return Transaction::commit();
        if( ! $this->txn ) return parent::commit(); 
        if( $this->lock ) return FALSE;
        return parent::commit();
    }
    
    public function format_query( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->format_query_args( $query, $args );
    }

    public function format_query_args($query, array $args) {
        if( ! $args || count( $args ) < 1 ) return $query;
        $conn = $this;
        return \Gaia\DB\Query::format( 
            $query, 
            $args, 
            function($v) use( $conn ){ return $conn->quote( $v ); }
            );
    }
    
    public function __toString(){
        return print_r( $this, TRUE);
    }
}