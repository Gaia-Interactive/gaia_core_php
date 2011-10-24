<?php
namespace Gaia\Stockpile\Storage\MySQLi;
use \Gaia\DB\Driver\MySQLi;
use \Gaia\Stockpile\Exception;
use \Gaia\Stockpile\Storage\Iface;
use \Gaia\Store;

class Core implements IFace {
    protected $db;
    protected $app;
    protected $user_id;
    public function __construct( MySQLi $db, $app, $user_id, $dsn){
        $this->db = $db;
        $this->app = $app;
        $this->user_id = $user_id;
        if( ! \Gaia\Stockpile\Storage::isAutoSchemaEnabled() ) return;
        $cache = function_exists('apc_fetch') ? new Store\Gate( new Store\Apc() ) : new Store\KVP;
        $key = 'stockpile/storage/__create/' . md5( $dsn . '/' . $app . '/' . get_class( $this ) );
        if( $cache->get( $key ) ) return;
        if( ! $cache->add( $key, 1, 60 ) ) return;
        $this->create();
    }
    
    public function create(){
        $table = $this->table();
        $rs = $this->execute('SHOW TABLES LIKE %s', $this->table());
        $row = $rs->fetch_array(MYSQLI_NUM);
        if( ! $row ) return $this->execute($this->sql('CREATE'));
    }
    
    protected function table(){
        return $this->app . '_stockpile_' . constant(get_class( $this ) . '::TABLE' );
    }
    
    protected function sql( $name ){
        return $this->injectTableName( constant(get_class($this) . '::SQL_' . $name) );
    }
    
    protected function injectTableName( $query ){
        return str_replace('{TABLE}', $this->table(), $query );
    }
    
    protected function execute( $query /*, .... */ ){
        $args = func_get_args();
        array_shift( $args );
        $rs = $this->db->query( $qs = $this->db->format_query_args( $query, $args ) );
        if( ! $rs ) throw new Exception('database error', array('db'=> $this->db, 'query'=>$qs ) );
        return $rs;
    }
}