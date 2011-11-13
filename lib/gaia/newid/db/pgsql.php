<?php
namespace Gaia\NewID\DB;
use Gaia\NewId\Iface;
use Gaia\Exception;
use Gaia\Store;

class PGSQL implements Iface {

    protected $app;
    protected $db;
    
    public function __construct(\Gaia\DB $db, $app = 'default' ){
        if( ! $db->isa('pgsql') && ! $db->isa('postgre') ) {
            trigger_error('invalid pdo', E_USER_ERROR);
            exit;
        }
        if( ! $db->isa('gaia\db\except') ) $db = new \Gaia\DB\Except( $db );
        $this->db = $db;
        if( ! preg_match('/^[a-z0-9_]+$/', $app) ) throw new Exception('invalid-app');
        $this->app = $app; 
    }

    public function id(){
        $rs = $this->db->execute('SELECT nextval(?) as id', $this->app);
        $row = $rs->fetch();
        if( ! $row ) {
            throw new Exception('invalid-sequence', $rs );
        }
        
        $id = strval($row['id']);
        if( ! ctype_digit( $id ) ){
            throw new Exception('invalid-sequence', $row );
        }
        return $id;
    }
    
   /**
    * return a list of new ids
    */
    public function ids( $ct = 1 ){
         $ids = array();
         if( $ct < 1 ) $ct = 1;
         while( $ct-- > 0 ) $ids[] = $this->id();
         return $ids;
    }
    
    public function init(){
        $rs = $this->db->execute('SELECT sequence_name FROM information_schema.sequences WHERE sequence_name = ?', $this->app);
        if( ! $stmt->fetch() ) {
            $rs = $this->db->execute(sprintf("CREATE SEQUENCE %s", $this->app));
        }
    }
    
    public function testInit(){
        $this->db->execute(sprintf("CREATE TEMPORARY SEQUENCE %s", $this->app));
    }
}