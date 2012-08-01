<?php
namespace Gaia\Affiliate;
use Gaia\Store;
use Gaia\DB;
use Gaia\Exception;

class SQLite implements Iface {

    protected $db;
    protected $table;

    public function __construct( $db, $table ){
        if( ! is_scalar( $table ) || ! preg_match('#^[a-z0-9_]+$#', $table ) ) {
            throw new Exception('invalid table name');
        }
        $this->db = function() use ( $db ){
            static $object;
            if( isset( $object ) ) return $object;
            if( is_scalar( $db ) ) $db = DB\Connection::instance( $db );
            if( ! $db instanceof DB\Iface ) throw new Exception('invalid db object');
            if( ! $db->isA('sqlite') ) throw new Exception('db object not sqlite');
            if( ! $db->isA('Gaia\DB\Except') ) $db = new DB\Except( $db );
            return $object = $db;
        };
        $this->table = $table;
    }
    
    public function search( array $identifiers ){
        $db = $this->db();
        $table = $this->table;
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $result = $hashes = array();
        foreach( $identifiers as $identifier ){
            $hash =sha1($identifier, true);
            $hashes[ $hash ] = $identifier;
            $result[ $identifier ] = NULL;
        }
        
        $rs = $db->execute("SELECT `affiliate`, `identifier` FROM `$table` WHERE `hash` IN ( %s )", array_keys($hashes) );
        $ids = array();
        while( $row = $rs->fetch() ){
            $result[$row['identifier']] =  $row['affiliate'];
        }
        $rs->free();
        
        foreach( array_keys( $result, NULL, TRUE ) as $key ){
            unset( $result[ $key ] );
        }
        return $result;
    }
        
    public function get( array $affiliates ){
        if( ! $affiliates ) return array();
        $result = array_fill_keys( $affiliates, array() );
        $db = $this->db();
        $table = $this->table;
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $rs = $db->execute("SELECT affiliate, `identifier` FROM `$table` WHERE `affiliate` IN ( %i )", $affiliates );
        $result = array();
        while( $row = $rs->fetch() ){
            $result[ $row['affiliate'] ][] = $row['identifier'];
        }
        $rs->free();     
        return $result;
    }
    
    public function findRelated( array $identifiers ){
        return Util::findRelated( $this, $identifiers );
    }
    
    public function join( array $identifiers ){
        return $this->joinRelated( $this->findRelated($identifiers) );
    }
    
    public function joinRelated( array $related ){
        $affiliate = NULL;
        foreach( $related as $identifier => $affiliate ){
            if( $affiliate ) break;            
        }
        
        if( ! $affiliate ) $affiliate = Util::newID();
        $db = $this->db();
        $table = $this->table;
        $local_txn =  DB\Transaction::claimStart();
        DB\Transaction::add( $db );
        $sql_insert = "INSERT OR IGNORE INTO `$table` (`affiliate`, `identifier`, `hash`) VALUES (%i, %s, %s)";
        $sql_update = "UPDATE `$table` set `affiliate` = %i WHERE `hash` = %s";
        foreach( $related as $identifier => $_id ){
            if( $_id == $affiliate ) continue;
            $related[ $identifier ] = $affiliate;
            $hash = sha1( $identifier, TRUE);
            $rs = $db->execute( $sql_insert, $affiliate, $identifier,  $hash );
            if( $rs->affected() < 1 ){
                $db->execute( $sql_update, $affiliate, $hash );
            }
        }
        if( $local_txn && ! DB\Transaction::commit() ) {
            throw new Exception('database error: unable to commit transaction', $db );
        }
        return $related;
    }
    
    public function delete( array $identifiers ){
        $db = $this->db();
        $table = $this->table;
        $local_txn =  DB\Transaction::claimStart();
        $hashes = array();
        foreach( $identifiers as $identifier ){
            $hashes[] = sha1($identifier, true);
        }
        $db->execute("DELETE FROM `$table` WHERE `hash` IN ( %s )", $hashes );
        
        if( $local_txn && ! DB\Transaction::commit() ) {
            throw new Exception('database error: unable to commit transaction', $db );
        }
    }
    
    public function initialize(){
        $db = $this->db();
        $rs = $db->execute("SELECT name FROM sqlite_master WHERE type = %s AND name = %s", 'table', $this->table );
        if( $rs->fetch() ) return;
        foreach(explode(';', $this->schema() ) as $query ) {
            $query = trim($query);
            if( strlen( $query ) < 1 ) continue;
            $db->execute( $query );
        }
    }
    
    
    protected function db(){
        $db = $this->db;
        return $db();
    }
    
    public function schema(){
        $table = $this->table;
        $index = $table . '_affiliate_idx';
        return 
        "CREATE TABLE IF NOT EXISTS `$table` ( 
            `identifier` TEXT NOT NULL, 
            `hash` BLOB NOT NULL, 
            `affiliate` INT NOT NULL,
            UNIQUE(`hash`)
            );
            
         CREATE INDEX IF NOT EXISTS `$index` ON `$table` (`affiliate`);
         ";
    }
}
