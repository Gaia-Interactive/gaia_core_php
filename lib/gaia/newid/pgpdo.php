<?php
namespace Gaia\NewID;
use Gaia\Exception;
use Gaia\Store;

class PgPDO implements Iface {

    protected $app;
    protected $db;
    protected $cache;
    
    public function __construct($app, \PDO $db, Store\Iface $cache = NULL ){
        $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if( $driver !== 'pgsql' ) {
            trigger_error('invalid pdo', E_USER_ERROR);
            exit;
        }
        $this->db = $db;
        if( ! preg_match('/^[a-z0-9_]+$/', $app) ) throw new Exception('invalid-app');
        $this->app = $app; 
        if( ! $cache ) $cache = new Store\KVP;
        $this->cache = new Store\Prefix( $cache, __CLASS__ . '/' . $app);

    }

    public function id(){
        $stmt = $this->db->prepare('SELECT nextval(?)');
        $rs = $stmt->execute(array($this->app));
        if( ! $rs )throw new Exception('database-error', $this->db);
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        if( ! $row ) {
            throw new Exception('invalid-sequence', $rs );
        }
        
        $id = strval($row[0]);
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
        $key = 'createtable';
        if( $this->cache->get($key )) return;
        if( ! $this->cache->add($key, 1, 5)) return;
        $stmt = $this->db->prepare('SELECT sequence_name FROM information_schema.sequences WHERE sequence_name = ?');
        $rs = $stmt->execute(array($this->app));
        if( ! $rs )throw new Exception('database-error', $this->db);
        if( ! $stmt->fetch(\PDO::FETCH_ASSOC) ) {
            $rs = $this->db->query(sprintf("CREATE SEQUENCE %s", $this->app));
            if( ! $rs )throw new Exception('database-error', $this->db);
        }
        $this->cache->set($key, 1, 60);
        
        ;
    }
    
    public function testInit(){
        $rs = $this->db->query(sprintf("CREATE TEMPORARY SEQUENCE %s", $this->app));
        if( ! $rs )throw new Exception('database-error', $stmt);
    }
}