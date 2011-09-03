<?php
namespace Gaia\DB\Driver;

class MySQLi extends \MySQLi implements \Gaia\DB\Transaction_Iface {
    
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
        if( $this->lock ) return FALSE;
        $rs = parent::close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function prepare(){
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
        parent::close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit(){
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
        $modify_funcs = array(
            's' => function($v) use ($conn) { return "'".$conn->real_escape_string($v)."'"; },
            'i' => function($v) { $v = strval($v); return preg_match('/^-?[1-9]([0-9]+)?$/', $v ) ? $v : 0; },
            'f' => function($v) {  $v = strval($v); return preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $v ) ? $v : 0; }
        );
    
        return preg_replace_callback(
            '/%([sif%])/',
            function ($matches) use ($conn, &$args, $modify_funcs) {
                if ($matches[1] == '%') {
                    return '%';
                }
                if (!count($args)) {
                    throw new Exception("Missing values!");
                }
                $arg = array_shift($args);
    
                if ($arg instanceof Traversable) {
                    $arg = iterator_to_array($arg);
                    $arg = array_map($modify_funcs[$matches[1]], $arg);
                    return implode(', ', $arg);
                } elseif (is_array($arg)) {
                    $arg = array_map($modify_funcs[$matches[1]], $arg);
                    return implode(', ', $arg);
                } else {
                    $func = $modify_funcs[$matches[1]];
                    return $func($arg);
                }
    
    
            },
            $query
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
}