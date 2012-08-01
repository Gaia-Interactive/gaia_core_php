<?php
namespace Gaia\Stratum;
use Gaia\DB;
use Gaia\Exception;

class SQLite implements Iface {
    
    protected $prefix;
    protected $dsn;
    protected $owner;
    
    public function __construct( $owner, $dsn, $prefix = ''  ){
        $this->prefix = $prefix;
        $this->dsn = $dsn;
        $this->owner = $owner;
    }
    
    
    public function store( $constraint, $stratum ){
        $db = $this->db();
        $table = $this->table();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "INSERT OR IGNORE INTO `$table` 
            (`owner`, `constraint`, `stratum`) VALUES (%i, %s, %i)";
        $rs = $db->execute( $sql, $this->owner, $constraint, $stratum );
        if( $rs->affected() ) return;
        $sql = "UPDATE `$table` SET `stratum` = %i WHERE `owner` = %i AND `constraint` = %s";
        $db->execute($sql, $stratum, $this->owner, $constraint );
        
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
        $where = array($db->prep_args('`owner` = %i', array( $this->owner ) ) );
        if( $search !== NULL ) $where[] = $db->prep_args("`stratum` IN( %s )", array($search) );
        if( $min !== NULL ) $where[] = $db->prep_args("`stratum` >= %i", array($min) );
        if( $max !== NULL ) $where[] = $db->prep_args("`stratum` <= %i", array($max) );
        $where = implode(' AND ', $where );
        $sql = "SELECT `constraint`, `stratum` FROM `$table` WHERE $where ORDER BY `stratum` $sort";
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
        $index = $table . '_owner_stratum_idx';
        return 
            "CREATE TABLE IF NOT EXISTS `$table` (
                `rowid` INTEGER PRIMARY KEY AUTOINCREMENT,
                `owner` BIGINT NOT NULL,
                `constraint` TEXT NOT NULL,
                `stratum` INT UNSIGNED NOT NULL,
                UNIQUE (`owner`,`constraint`)
            );
            
            CREATE INDEX IF NOT EXISTS `$index` ON `$table` (`owner`,`stratum`)"; 
            
    }
    
    protected function table(){
        return  $this->prefix . 'stratum';
    }
    
    protected function db(){
        $db = DB\Connection::instance( $this->dsn );
        if( ! $db->isa('sqlite') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        return $db;
    }
}
