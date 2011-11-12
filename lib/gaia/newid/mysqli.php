<?php
namespace Gaia\NewID;

class MySQLI extends MySQL implements Iface {
    
    public function __construct( $app, \MySQLI $db, $cache = NULL ){
        parent::__construct( $app, $db, $cache );
    }

    protected function fetch_assoc( $rs ){
        return $rs->fetch_assoc();
    }
    
    protected function dbinfo(){
        return 'mysqli://' . $this->db->host_info;
    }
    
    protected function free( $rs ){
        $rs->free();
    }
}