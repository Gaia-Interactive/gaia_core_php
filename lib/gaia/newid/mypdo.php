<?php
namespace Gaia\NewID;

class MyPDO extends MySQL implements Iface {
    
    protected $dbinfo;
    
    public function __construct( $app, \PDO $db, $cache ){
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if( $driver !== 'mysql' ) {
            trigger_error('invalid pdo', E_USER_ERROR);
            exit;
        }
        parent::__construct( $app, $db, $cache );
        $driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        $conn =  $this->db->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
        $version = $this->db->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $this->dbinfo = $driver . ':' . $conn . ' version ' . $version;
    }

    protected function fetch_assoc( $rs ){
        return $rs->fetch(\PDO::FETCH_ASSOC);
    }
    
    protected function dbinfo(){
        return $this->dbinfo;
    }
    
    protected function free( $rs ){
        while( $rs->fetch() );
    }
}