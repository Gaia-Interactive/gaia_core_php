<?php
namespace Gaia\Stratum;
use Gaia\DB;
use Gaia\Exception;

class IntMySQL implements Iface {
    
    protected $dsn;
    protected $table;

    public function __construct( $dsn, $table ){
        $this->dsn = $dsn;
        $this->table = $table;
    }
    
    
    public function store( $constraint, $stratum ){
        $constraint = strval( $constraint );
        if( ! ctype_digit( $constraint ) ) throw new Exception('only integer constraint supported');
        $db = $this->db();
        $table = $this->table();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );

        $sql = "INSERT INTO $table 
            (`constraint`, `stratum`) VALUES (%i, %i) 
            ON DUPLICATE KEY UPDATE `stratum` = VALUES(`stratum`)";
        $db->execute( $sql, $constraint, $stratum );
    }
    
    public function delete( $constraint ){
        $constraint = strval( $constraint );
        if( ! ctype_digit( $constraint ) ) throw new Exception('only integer constraint supported');
        $db = $this->db();
        $table = $this->table();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "DELETE FROM $table WHERE `constraint` = %i";
        $rs = $db->execute( $sql, $constraint );
        return $rs->affected() > 0;
    }
    
    public function query( array $params = array() ){
        $search = NULL;
        $min = NULL;
        $max = NULL;
        $sort = 'ASC';
        $limit = NULL;
        $result = array();
        
        if( isset( $params['search'] ) ) $search = $params['search'];
        if( isset( $params['min'] ) ) $min = $params['min'];
        if( isset( $params['max'] ) ) $max = $params['max'];
        if( isset( $params['sort'] ) ) $sort = $params['sort'];
        if( isset( $params['limit'] ) ) $limit = $params['limit'];
        if( $limit !== NULL ) $limit = str_replace(' ', '', $limit );
        $sort = strtoupper( $sort );
        if( $sort != 'DESC' ) $sort = 'ASC';
        $db = $this->db();
        $table = $this->table();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $where = array();
        if( $search !== NULL ) $where[] = $db->prep_args("`stratum` IN( %s )", array($search) );
        if( $min !== NULL ) $where[] = $db->prep_args("`stratum` >= %i", array($min) );
        if( $max !== NULL ) $where[] = $db->prep_args("`stratum` <= %i", array($max) );
        if( $where ) $where = 'WHERE ' . implode(' AND ', $where );
        $sql = "SELECT `constraint`, `stratum` FROM `{$table}` {$where} ORDER BY `stratum` $sort";
        if( $limit !== NULL && preg_match("#^([0-9]+)((,[0-9]+)?)$#", $limit ) ) $sql .= " LIMIT " . $limit;
        //print "\n$sql\n";
        $rs = $db->execute( $sql );
        while( $row = $rs->fetch() ) {
            $result[ $row['constraint'] ] = $row['stratum'];
        }
        $rs->free();
        return $result;
    }
    
    public function init(){
        $db = $this->db();
        $rs = $db->execute("SHOW TABLES LIKE %s", $this->table() );
        if( $rs->fetch() ) return;
        $db->execute( $this->schema() );
    }
    
    
    public function schema(){
        $table = $this->table();
        return 
            "CREATE TABLE IF NOT EXISTS $table (
                `rowid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `constraint` BIGINT UNSIGNED NOT NULL,
                `stratum` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`rowid`),
                UNIQUE `constraint` (`constraint`),
                INDEX `stratum` (`stratum`)
            ) ENGINE=InnoDB"; 
            
    }
    
    public function table(){
        return  $this->table;
    }
    
    protected function db(){
        $db = DB\Connection::instance( $this->dsn );
        if( ! $db->isa('mysql') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        return $db;
    }
}
