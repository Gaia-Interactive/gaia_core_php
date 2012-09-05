<?php
namespace Gaia\Stratum;
use Gaia\DB;
use Gaia\Exception;

class SQLite implements Iface {
    
    protected $table;
    protected $dsn;
    
    public function __construct( $dsn, $table ){
        $this->table = $table;
        $this->dsn = $dsn;
    }
    
    
    public function store( $constraint, $stratum ){
        $db = $this->db();
        $table = $this->table();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "INSERT OR IGNORE INTO `$table` 
            (`constraint`, `stratum`) VALUES ( %s, %i)";
        $rs = $db->execute( $sql, $constraint, $stratum );
        if( $rs->affected() ) return;
        $sql = "UPDATE `$table` SET `stratum` = %i WHERE `constraint` = %s";
        $db->execute($sql, $stratum, $constraint );
    }
    
    public function delete( $constraint ){
        $db = $this->db();
        $table = $this->table();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "DELETE FROM $table WHERE `constraint` = %s";
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
        $where = ( $where ) ? 'WHERE ' . implode(' AND ', $where ) : '';
        $sql = "SELECT `constraint`, `stratum` FROM `$table` $where ORDER BY `stratum` $sort";
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
        $rs = $db->execute("SELECT name FROM sqlite_master WHERE type = %s AND name = %s", 'table', $this->table() );
        if( $rs->fetch() ) return;
        foreach(explode(';', $this->schema() ) as $query ) {
            $db->execute( $query );
        }
    }
    
    
    public function schema(){
        $table = $this->table();
        $index = $table . '_idx';
        return 
            "CREATE TABLE IF NOT EXISTS `$table` (
                `rowid` INTEGER PRIMARY KEY AUTOINCREMENT,
                `constraint` TEXT NOT NULL,
                `stratum` INT UNSIGNED NOT NULL,
                UNIQUE (`constraint`)
            );
            
            CREATE INDEX IF NOT EXISTS `$index` ON `$table` (`stratum`)"; 
            
    }
    
    protected function table(){
        return  $this->table;
    }
    
    protected function db(){
        $db = DB\Connection::instance( $this->dsn );
        if( ! $db->isa('sqlite') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        return $db;
    }
}
