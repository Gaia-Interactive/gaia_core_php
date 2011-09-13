<?php
namespace Gaia\NewID;

class MyPDO extends MySQL implements Iface {
    
    public function __construct( \Gaia\DB\Driver\PDO $db, $cache, $app ){
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if( $driver !== 'mysql' ) {
            trigger_error('invalid pdo', E_USER_ERROR);
            exit;
        }
        parent::__construct( $db, $cache, $app );
    }

    protected function fetch_assoc( $rs ){
        return $rs->fetch(\PDO::FETCH_ASSOC);
    }
    
    protected function dbinfo(){
        return $this->db->dsn();
    }
    
    protected function free( $rs ){
        while( $rs->fetch() );
    }
}