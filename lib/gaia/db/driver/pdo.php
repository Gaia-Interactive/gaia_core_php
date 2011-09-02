<?php
namespace Gaia\DB\Driver;

class PDO extends \PDO implements \Gaia\DB\Transaction_Iface {


    protected $lock = FALSE;
    protected $txn = FALSE;
    
    public function execute( $query /*, ... */ ){
        $args = func_get_args();
        array_shift($args);
        return $this->query( $this->format_query_args( $query, $args ) );
    }
    
    public function query( $query ){
        if( $this->lock ) return FALSE;
        $args = func_get_args();
        $res = call_user_func_array( array('\PDO', 'query'), $args);
        if( $res ) return $res;
        if( $this->txn ) {
            if( is_callable( $this->txn ) ) call_user_func( $this->txn, $this );
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
            if( is_callable( $this->txn ) ) call_user_func( $this->txn, $this );
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function begin( $txn = FALSE ){
        if( $this->lock ) return FALSE;
        $this->txn = $txn;
        return parent::beginTransaction();
    }
    
    public function beginTransaction(){
        if( $this->lock ) return FALSE;
        return parent::beginTransaction();
    }
    
    public function rollback(){
        if( ! $this->txn ) return parent::rollback(); 
        if( $this->lock ) return TRUE;
        $rs = parent::rollback();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit(){
        if( ! $this->txn ) return parent::rollback(); 
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
            's' => function($v) use ($conn) { return $conn->quote($v); },
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
        return print_r( $this, TRUE);
    }
}