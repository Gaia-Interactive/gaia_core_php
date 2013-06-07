<?php
namespace Gaia\Identifier;
use Gaia\Exception;
use Gaia\DB\Transaction;
use Gaia\DB;

class MySQL implements Iface {
    
    protected $db;
    protected $table;
    
    public function __construct( $db, $table ){
        $this->db = $db;
        $this->table = $table;
    }
    
    public function byId( $request ){
        $ids = ( $scalar = is_scalar( $request ) ) ? array( $request ) : $request;
        $table = $this->table();
        $db = $this->db();
        $sql = "SELECT * FROM `$table` WHERE `id` IN ( %i )";
        $map = array();
        foreach( array_chunk( $ids, 500 ) as $_ids ){
            if( ! $_ids ) break;
            $rs = $db->execute( $sql, $_ids );
            while( $row = $rs->fetch() ) {
                $map[ $row['id'] ] = $row['name'];
            }
            $rs->free();
        }
        return $scalar ? array_pop( $map ) : $map;    
    }
    
    public function byName( $request  ){
        $names = ( $scalar = is_scalar( $request ) ) ? array( $request ) : $request;
        $table = $this->table();
        $db = $this->db();
        $sql = "SELECT * FROM `$table` WHERE `name` IN ( %s )";
        $map = array();
        foreach( array_chunk( $names, 100 ) as $_names ){
            if( ! $_names ) break;
            
            $rs = $db->execute( $sql, $_names );
            while( $row = $rs->fetch() ) {
                $map[ $row['name'] ] = $row['id'];
            }
            $rs->free();
        }
        return $scalar ? array_pop( $map ) : $map;    
    }
    
    public function delete( $id, $name ){
        $table = $this->table();
        $db = $this->db();
        $sql = "DELETE FROM `$table` WHERE `id` = %i AND `name` = %s";
        $rs = $db->execute( $sql, $id, $name );
        return TRUE;
    }
    
    public function store( $id, $name, $strict = FALSE ){
        $namelen = strlen( $name );
        if(  $namelen < 1 || $namelen > 255 ){
            throw new Exception('invalid-name', $name);
        }
        
        if( ! ctype_digit( strval( $id ) ) ){
            throw new Exception('invalid-id', $id);
        }
        
        $namecheck = $this->byID( $id );
        if( $namecheck == $name ) return TRUE;
        if( $namecheck !== null ){
            $this->delete($id, $namecheck );
        }
        
        $idcheck = $this->byName( $name );
        if( $id != $idcheck && $idcheck !== null ){
            if( $strict ) throw new Exception('name-taken');
            $this->delete($idcheck, $name );
        }
        $table = $this->table();
        $db = $this->db();
        $sql = "INSERT INTO `$table` (`id`, `name`) VALUES (%i, %s)";
        if( ! $strict ) $sql .= " ON DUPLICATE KEY UPDATE `id` = VALUES(`id`), `name` = VALUES(`name`)";
        $rs = $db->execute($sql, $id, $name );
        return TRUE;
    }
    
    public function batch( \Closure $closure, array $options = NULL ){
        $table = $this->table();
        $db = $this->db();
        $sql = "SELECT * FROM `$table`";
        
        $clauses = array();
        if( $options ){
            if( isset( $options['min'] ) ){
                $clauses[] = $db->prep('`id` > %i', $options['min']);
            }
            if( isset( $options['max'] ) ){
                $clauses[] = $db->prep('`id` < %i', $options['max']);
            }
            
            if( $clauses ) $sql .= ' WHERE ' . implode(' AND ', $clauses);
            
            if( isset( $options['limit']  ) && preg_match("#^([0-9]+)((,[0-9]+)?)$#", $options['limit'] )){
                $sql .= " LIMIT " . $options['limit'];
            }
        }
        
        $rs = $db->execute( $sql );
        
        while( $row = $rs->fetch() ){
            $closure( $row['id'], $row['name'] );
        }
        $rs->free();
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
            "CREATE TABLE IF NOT EXISTS `$table` (" .
            "`id` bigint unsigned NOT NULL PRIMARY KEY, " .
		    "`name` varchar(255) NOT NULL, " .
            "UNIQUE KEY  (`name`) " .
			") ENGINE=InnoDB"; 
            
    }
    
    public function table(){
        return  $this->table;
    }
    
    protected function db(){
        if( $this->db instanceof \Closure ){
            $mapper = $this->db;
            $db = $mapper( $this->table() );
        } elseif( is_scalar( $this->db ) ){
            $db = DB\Connection::instance( $this->db );
        } else {
            $db = $this->db;
        }
        if( ! $db instanceof DB\Iface ) throw new Exception('invalid db');
        if( ! $db->isa('mysql') ) throw new Exception('invalid db');
        if( ! $db->isa('gaia\db\extendediface') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        if( ! Transaction::atStart() ) Transaction::add( $db );
        return $db;
    }
}