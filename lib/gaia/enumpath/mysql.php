<?php
namespace Gaia\EnumPath;
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
    
    public function spawn( $parent = NULL ){
        $parent_path = ( $parent !== NULL ) ? $this->pathById( $parent ) : '';
        $db = $this->db();
        $table = $this->table();
        Transaction::start();
        $rs = $db->execute("INSERT INTO `$table` VALUES ()");
        $id = $rs->insertId();
        $db->execute("UPDATE `$table` SET `path` = %s WHERE `id` = %i", $parent_path . Util::SEP . $id, $id);
        Transaction::commit();
        return $id;
        
    }
    
    public function alter( $id, $parent ){
        Util::validateId( $id );
        Util::validateId( $parent );
        $new_parent_path = $this->pathById( $parent );
        
        $new_path = $new_parent_path . Util::SEP . $id;
        $path = $this->pathById( $id );
        $old_len = strlen( $path );
        $db = $this->db();
        $table = $this->table();
        
        $db->execute("UPDATE `$table` SET `path` = CONCAT(%s, %s, SUBSTRING(`path`, $old_len)) WHERE `path` BETWEEN %s AND %s", $new_parent_path, Util::SEP, $path, $path . Util::SEP . 'z');
        return $new_path;
    }
    
    public function idsInPath( $path ){
        Util::validatePath( $path );
        $db = $this->db();
        $table = $this->table();
        $rs = $db->execute("SELECT `id`, `path` FROM `$table` WHERE `path` BETWEEN %s AND %s ORDER BY path ASC", $path, $path . 'z' );        
        $list = array();
        while( $row = $rs->fetch() ) $list[$row['path']] = $row['id'];
        $rs->free();
        return $list;
    }

    public function pathById( $input ){
        $db = $this->db();
        $table = $this->table();
        $rs = $db->execute("SELECT `id`, `path` FROM `$table` WHERE `id` IN( %i ) ORDER BY `path` ASC", $input );
        $list = array();
        while( $row = $rs->fetch() ) $list[$row['id']] = $row['path'];
        $rs->free();
        return is_scalar( $input ) ? array_pop( $list ) : $list;
    }
    
    public function separator(){
        return Util::SEP;
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
            "`id` int UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
            "`path` varchar(500) " .
            ") Engine=InnoDB"; 
            
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
        return $db;
    }
}