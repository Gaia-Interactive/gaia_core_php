<?php
namespace Gaia\Affiliate;
use Gaia\Store;
use Gaia\DB;
use Gaia\Exception;

class MySQL implements Iface {

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
            if( ! $db->isA('mysql') ) throw new Exception('db object not mysql');
            if( ! $db->isA('Gaia\DB\Except') ) $db = new DB\Except( $db );
            return $object = $db;
        };
    }
    
    public function affiliations( array $identifiers ){
        $db = $this->db();
        $table = $this->table();
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $result = $hashes = array();
        foreach( $identifiers as $identifier ){
            $hash =sha1($identifier, true);
            $hashes[ $hash ] = $identifier;
            $result[ $identifier ] = NULL;
        }
        
        $rs = $db->execute("SELECT `identifier`, `affiliation` FROM `$table` WHERE `hash` IN ( %s )", array_keys($hashes) );
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
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $table = $this->table();
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
        $db = $this->db();
        $table = $this->table();
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $affiliation = NULL;
        foreach( $related as $identifier => $affiliation ){
            if( $affiliation ) break;            
        }
        
        if( ! $affiliation ) $affiliation = Util::newID();
        
        $clauses = array();
        
        foreach( $related as $identifier => $_id ){
            if( $_id == $affiliation ) continue;
            $related[ $identifier ] = $affiliation;
            $clauses[] = $db->prep_args('(%s, %s, %i)', array( $identifier, sha1( $identifier, TRUE), $affiliation ) );
        }
        
        if( ! $clauses ) return $related;
        $sql = "INSERT INTO `$table` (`identifier`, `hash`, `affiliation` ) VALUES " . implode(',', $clauses ) . ' ON DUPLICATE KEY UPDATE `affiliation` = VALUES( `affiliation` )';
        $db->execute($sql);
        return $related;
    }
    
    public function delete( array $identifiers ){
        $db = $this->db();
        $table = $this->table();
        if( ! DB\Transaction::atStart() ) DB\Transaction::add( $db );
        $hashes = array();
        foreach( $identifiers as $identifier ){
            $hashes[] = sha1($identifier, true);
        }
        $db->execute("DELETE FROM `$table` WHERE `hash` IN ( %s )", $hashes );
    }
    
    public function initialize(){
        $this->db()->execute( $this->schema() );
    }
    
    
    protected function db(){
        $db = $this->db;
        return $db();
    }
    
    protected function table(){
        return $this->table_prefix . '_affiliate';
    }
    
    public function schema(){
        $table = $this->table();
        return 
        "CREATE TABLE IF NOT EXISTS `$table` ( 
            `row_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `identifier` VARCHAR(500) NOT NULL, 
            `hash` BINARY(20) NOT NULL, 
            `affiliation` BIGINT UNSIGNED NOT NULL,
            UNIQUE(`hash`), 
            INDEX (`affiliation`) 
            ) engine InnoDB";
    }
}
