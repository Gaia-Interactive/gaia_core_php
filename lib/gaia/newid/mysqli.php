<?php
namespace Gaia\NewID;

class MySQLI extends MySQL implements Iface {
    
    public function __construct( \MySQLI $db, $cache, $app ){
        parent::__construct( $db, $cache, $app );
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