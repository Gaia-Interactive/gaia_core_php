<?php
namespace Gaia\Stockpile\Storage\SQLite;
use \Gaia\Stockpile\Exception;
use \Gaia\DB\Transaction;
use \Gaia\Stockpile\Storage\Iface;

abstract class Core implements Iface {
    protected $db;
    protected $table;
    protected $user_id;
    public function __construct( \Gaia\DB $db, $table, $user_id ){
        if( ! $db->isa('sqlite') ) throw new Exception('invalid driver', $db );
        $this->db = $db;
        $this->table = $table;
        $this->user_id = $user_id;
    }
    
    public abstract function schema();
    
    public function create(){
        $table = $this->table();
        $rs = $this->execute("SELECT `name` FROM `sqlite_master` WHERE `type` = 'table' and `name` = %s", $this->table());
        $row = $rs->fetch();
        if( $row ) return TRUE;
        foreach(explode(';', $this->schema()) as $sql ) {
            $sql = trim( $sql );
            if( strlen( $sql ) < 1 ) continue;
            $rs = $this->execute($sql);
            if( ! $rs ) return FALSE;
        }
        return TRUE;
    }
    
    protected function table(){
        return $this->table;
    }
    
    protected function execute( $query /*, .... */ ){
        if( ! Transaction::atStart() ) Transaction::add( $this->db );
        $args = func_get_args();
        array_shift( $args );
        $rs = $this->db->execute( $qs = $this->db->prep_args( $query, $args ) );
        //print "#    " . $qs ."\n";
        if( ! $rs ){
            throw new Exception('database error', $this->dbInfo($qs) );
        }
        return $rs;
    }
    
    protected function dbinfo($qs = NULL){
        return  array('db'=> $this->db, 'query'=>$qs, 'error'=>$this->db->error());
    }
    
    protected function claimStart(){
        if( ! Transaction::claimStart() ) return FALSE;
        Transaction::add( $this->db );
        return TRUE;
    }
}