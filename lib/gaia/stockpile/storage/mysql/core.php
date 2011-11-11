<?php
namespace Gaia\Stockpile\Storage\MySQL;
use \Gaia\Stockpile\Exception;
use \Gaia\Stockpile\Storage\Iface;
use \Gaia\Store;
use \Gaia\DB\Transaction;

class Core implements Iface {
    protected $db;
    protected $app;
    protected $user_id;
    public function __construct( \Gaia\DB $db, $app, $user_id, $dsn){
        if( ! $db->isa('mysql') ) throw new Exception('invalid driver', $db );
       $this->db = $db;
        $this->app = $app;
        $this->user_id = $user_id;
        if( ! \Gaia\Stockpile\Storage::isAutoSchemaEnabled() ) return;
        $cache = new Store\Gate( new Store\Apc() );
        $key = 'stockpile/storage/__create/' . md5( $dsn . '/' . $app . '/' . get_class( $this ) );
        if( $cache->get( $key ) ) return;
        if( ! $cache->add( $key, 1, 60 ) ) return;
        $this->create();
    }
    
    public function create(){
        $table = $this->table();
        $rs = $this->execute('SHOW TABLES LIKE %s', $this->table());
        $row = $rs->fetch();
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
        if( ! Transaction::atStart() ) Transaction::add( $this->db );
        $args = func_get_args();
        array_shift( $args );
        $rs = $this->db->execute( $qs = $this->db->prep_args( $query, $args ) );
        if( ! $rs ) throw new Exception('database error', array('db'=> $this->db, 'query'=>$qs, 'error'=>$this->db->error()) );
        return $rs;
    }
}