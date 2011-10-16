<?php
namespace Gaia\DB\Driver;

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
        return $this->query( $this->format_query_args( $query, $args ) );
    }
    
	public function query($sql, $binds = FALSE, $return_object = TRUE){
        if( $this->lock ) return FALSE;
        $res = $this->core->query( $sql, $binds, $return_object );
        if( $res ) return $res;
        if( $this->txn ) {
            if( is_callable( $this->txn ) ) call_user_func( $this->txn, $this );
            $this->lock = TRUE;
        }
        return $res;
    }
    
    public function simple_query( $query ){
        if( $this->lock ) return FALSE;
        $res = $this->core->simple_query( $query );
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
        $rs = $this->core->close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function begin( $txn = FALSE ){
        return $this->trans_start();
    }
    
    public function trans_start(){
        if( $this->lock ) return FALSE;
        $this->txn = $txn;
        return $this->core->trans_start();
    }
    
    public function rollback(){
        return $this->trans_rollback();
    }
    
    public function trans_rollback(){
        if( ! $this->txn ) return $this->core->trans_rollback(); 
        if( $this->lock ) return TRUE;
        $rs = $this->core->trans_rollback();
        $this->core->close();
        $this->lock = TRUE;
        return $rs;
    }
    
    public function commit(){
        return $this->trans_complete();
    }
    
    public function trans_complete(){
        if( ! $this->txn ) return $this->core->trans_complete(); 
        if( $this->lock ) return FALSE;
        $res =  $this->core->trans_complete();
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
        $conn = $this->core;
        $modify_funcs = array(
            's' => function($v) use ($conn) { return "'".$conn->escape_str($v)."'"; },
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
        @ $res = print_r( $this, TRUE);
        return $res;
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
