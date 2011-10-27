<?php
namespace Gaia\DB\Driver;
use Gaia\DB\Connection;

class MySQLi extends \MySQLi implements \Gaia\DB\Iface {
    
    protected $lock = FALSE;
    protected $txn = FALSE;
    
    public function execute( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->query( $this->format_query_args( $query, $args ) );
    }
    
    public function query( $query, $mode = MYSQLI_STORE_RESULT ){
        if( $this->lock ) return FALSE;
        $res = parent::query( $query, $mode );
        if( $res ) return $res;
        if( $this->txn ) {
            if( is_callable( $this->txn ) ) call_user_func( $this->txn, $this );
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function multi_query( $query ){
        if( $this->lock ) return FALSE;
        $res = parent::multi_query( $query );
        if( $res ) return $res;
        if( $this->txn ) {
            if( is_callable( $this->txn ) ) call_user_func( $this->txn, $this );
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function real_query( $query ){
        if( $this->lock ) return FALSE;
        $res = parent::real_query( $query );
        if( $res ) return $res;
        if( $this->txn ) {
            if( is_callable( $this->txn ) ) call_user_func( $this->txn, $this );
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function close(){
        Connection::remove( $this );
        if( $this->lock ) return FALSE;
        $rs = parent::close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function prepare($query){
        trigger_error('unsupported method ' . __CLASS__ . '::' . __FUNCTION__, E_USER_ERROR);
        exit;
    }
    
    
    public function begin( $txn = FALSE ){
        if( $this->lock ) return FALSE;
        $this->txn = $txn;
        return $this->query('BEGIN WORK');
    }
    
    public function rollback(){
        if( ! $this->txn ) return parent::rollback(); 
        if( $this->lock ) return TRUE;
        $rs = parent::rollback();
        $this->close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit(){
        if( ! $this->txn ) return parent::commit(); 
        if( $this->lock ) return FALSE;
        $res = parent::commit();
        if( ! $res ) return $res;
        $this->txn = FALSE;
        return $res;
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
            function($v) use( $conn ){ return "'" . $conn->real_escape_string( $v ) . "'"; }
            );
    }
    
    public function __toString(){
        @ $res ='(Gaia\DB\MySQLi object - ' . "\n" .
            '  [affected_rows] => ' . $this->affected_rows . "\n" .
            '  [client_info] => ' . $this->client_info . "\n" .
            '  [client_version] => ' . $this->client_version . "\n" .
            '  [connect_errno] => ' . $this->connect_errno . "\n" .
            '  [connect_error] => ' . $this->connect_error . "\n" .
            '  [errno] => ' . $this->errno . "\n" .
            '  [error] => ' . $this->error . "\n" .
            '  [field_count] => ' . $this->field_count . "\n" .
            '  [host_info] => ' . $this->host_info . "\n" .
            '  [info] => ' . $this->info . "\n" .
            '  [insert_id] => ' . $this->insert_id . "\n" .
            '  [server_info] => ' . $this->server_info . "\n" .
            '  [server_version] => ' . $this->server_version . "\n" .
            '  [sqlstate] => ' . $this->sqlstate . "\n" .
            '  [protocol_version] => ' . $this->protocol_version . "\n" .
            '  [thread_id] => ' . $this->thread_id . "\n" .
            '  [warning_count] => ' . $this->warning_count . "\n" .
            '  [lock] => ' .( $this->lock ? 'TRUE' : 'FALSE') . "\n" .
            ')';
        return $res;
    }
    
    protected $modifiers;
}