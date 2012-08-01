<?php
namespace Gaia\Affiliate;
use Gaia\Store;
use Gaia\DB;
use Gaia\Exception;

class SQLite implements Iface {

    protected $db;
    protected $table_prefix;

    public function __construct( $db, $table_prefix ){
        if( ! is_scalar( $table_prefix ) || ! preg_match('#^[a-z0-9_]+$#', $table_prefix ) ) {
            throw new Exception('invalid table name');
        }
        $this->table_prefix = $table_prefix;
        $this->db = function() use ( $db ){
            static $object;
            if( isset( $object ) ) return $object;
            if( is_scalar( $db ) ) $db = DB\Connection::instance( $db );
            if( ! $db instanceof DB\Iface ) throw new Exception('invalid db object');
            if( ! $db->isA('sqlite') ) throw new Exception('db object not sqlite');
            if( ! $db->isA('Gaia\DB\Except') ) $db = new DB\Except( $db );
            return $object = $db;
        };
    }
    
    public function affiliations( array $identifiers ){
        $db = $this->db();
        $table = $this->table();
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $result = array_fill_keys( $identifiers, NULL );
        $rs = $db->execute("SELECT `affiliation`, `identifier` FROM `$table` WHERE `identifier` IN ( %s )", $identifiers );
        $ids = array();
        while( $row = $rs->fetch() ){
            $result[$row['identifier']] =  $row['affiliation'];
        }
        $rs->free();
        
        foreach( array_keys( $result, NULL, TRUE ) as $key ){
            unset( $result[ $key ] );
        }
        return $result;
    }
        
    public function identifiers( array $affiliations ){
        if( ! $affiliations ) return array();
        $result = array_fill_keys( $affiliations, array() );
        $db = $this->db();
        $table = $this->table();
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $rs = $db->execute("SELECT `affiliation`, `identifier` FROM `$table` WHERE `affiliation` IN ( %i )", $affiliations );
        $result = array();
        while( $row = $rs->fetch() ){
            $result[ $row['affiliation'] ][] = $row['identifier'];
        }
        $rs->free();     
        return $result;
    }
    
    public function related( array $identifiers ){
        return Util::related( $this, $identifiers );
    }
    
    public function join( array $identifiers ){
        return $this->_joinRelated( $this->related($identifiers) );
    }
    
    public function _joinRelated( array $related ){
        $affiliation = NULL;
        foreach( $related as $identifier => $affiliation ){
            if( $affiliation ) break;            
        }
        
        if( ! $affiliation ) $affiliation = Util::newID();
        $db = $this->db();
        $table = $this->table();
        $local_txn =  DB\Transaction::claimStart();
        DB\Transaction::add( $db );
        $sql_insert = "INSERT OR IGNORE INTO `$table` (`identifier`, `affiliation`) VALUES (%s, %i)";
        $sql_update = "UPDATE `$table` set `affiliation` = %i WHERE `identifier` = %s";
        foreach( $related as $identifier => $_id ){
            if( $_id == $affiliation ) continue;
            $related[ $identifier ] = $affiliation;
            $rs = $db->execute( $sql_insert, $identifier, $affiliation );
            if( $rs->affected() < 1 ){
                $db->execute( $sql_update, $affiliation, $identifier );
            }
        }
        if( $local_txn && ! DB\Transaction::commit() ) {
            throw new Exception('database error: unable to commit transaction', $db );
        }
        return $related;
    }
    
    public function delete( array $identifiers ){
        $db = $this->db();
        $table = $this->table();
        $local_txn =  DB\Transaction::claimStart();
        $db->execute("DELETE FROM `$table` WHERE `identifier` IN ( %s )", $identifiers );
        
        if( $local_txn && ! DB\Transaction::commit() ) {
            throw new Exception('database error: unable to commit transaction', $db );
        }
    }
    
    public function initialize(){
        $db = $this->db();
        $rs = $db->execute("SELECT name FROM sqlite_master WHERE type = %s AND name = %s", 'table', $this->table() );
        if( $rs->fetch() ) return;
        foreach(explode(';', $this->schema() ) as $query ) {
            $query = trim($query);
            if( strlen( $query ) < 1 ) continue;
            $db->execute( $query );
        }
    }
    
    
    protected function table(){
        return $this->table_prefix . '_affiliate';
    }
    
    protected function db(){
        $db = $this->db;
        return $db();
    }
    
    public function schema(){
        $table = $this->table();
        $index = $table . '_affiliate_idx';
        return 
        "CREATE TABLE IF NOT EXISTS `$table` ( 
            `identifier` TEXT NOT NULL, 
            `affiliation` INT NOT NULL,
            UNIQUE(`identifier`)
            );
            
         CREATE INDEX IF NOT EXISTS `$index` ON `$table` (`affiliation`);
         ";
    }
}
