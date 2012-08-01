<?php
namespace Gaia\Affiliate;
use Gaia\Store;
use Gaia\DB;
use Gaia\Exception;

class MySQL implements Iface {

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
            if( ! $db->isA('mysql') ) throw new Exception('db object not mysql');
            if( ! $db->isA('Gaia\DB\Except') ) $db = new DB\Except( $db );
            return $object = $db;
        };
        $this->table = $table;
    }
    
    public function search( array $identifiers ){
        $db = $this->db();
        $table = $this->table;
        
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
        
        $clauses = array();
        $db = $this->db();
        $table = $this->table;
        foreach( $related as $identifier => $_id ){
            if( $_id == $affiliate ) continue;
            $related[ $identifier ] = $affiliate;
            $clauses[] = $db->prep_args('(%i, %s, %s)', array( $affiliate, $identifier, sha1( $identifier, TRUE) ) );
        }
        
        if( ! $clauses ) return $related;
        $sql = "INSERT INTO `$table` (`affiliate`, `identifier`, `hash`) VALUES " . implode(',', $clauses ) . ' ON DUPLICATE KEY UPDATE `affiliate` = VALUES( `affiliate` )';
        $db->execute($sql);
        return $related;
    }
    
    public function delete( array $identifiers ){
        $db = $this->db();
        $table = $this->table;
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
    
    public function schema(){
        $table = $this->table;
        return 
        "CREATE TABLE IF NOT EXISTS `$table` ( 
            `row_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `identifier` VARCHAR(500) NOT NULL, 
            `hash` BINARY(20) NOT NULL, 
            `affiliate` BIGINT UNSIGNED NOT NULL,
            UNIQUE(`hash`), 
            INDEX (`affiliate`) 
            ) engine InnoDB";
    }
}
