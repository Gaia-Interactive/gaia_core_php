<?php
namespace Gaia\Skein;
use Gaia\DB;
use Gaia\Exception;

class SQLite implements Iface {
    
    protected $mapper;
    protected $thread;
    
    public function __construct( \Closure $mapper, $thread ){
        $this->mapper = $mapper;
        $this->thread = $thread;
    }
    
    public function count(){
        return Util::count( $this->shardSequences() );
    }
    
    public function get( $id ){
        if( is_array( $id ) ) return $this->multiget( $id );
        $res = $this->multiget( array( $id ) );
        return isset( $res[ $id ] ) ? $res[ $id ] : NULL;
    }
    
    protected function multiGet( array $ids ){
        $result = array();
        foreach( Util::parseIds( $ids ) as $shard=>$sequences ){
            $table= 't_' . $shard;
            $db = $this->db( $table );
            $sql = "SELECT `sequence`,`data` FROM `$table` WHERE `thread` = %s AND `sequence` IN( %i )";
            $rs = $db->execute( $sql, $this->thread, $sequences );
            while( $row = $rs->fetch() ){
                $id = Util::composeId( $shard, $row['sequence'] );
                $result[ $id ] = $this->unserialize($row['data']);
                if( ! is_array( $result[ $id ] ) ) $result[ $id ] = array();
            }
            $rs->free();
        }
        return $result;
    }
    
    
    public function add( array $data ){
        $shard = Util::currentShard();
        $table = 't_index';
        $db = $this->db($table);
        DB\Transaction::start();
        DB\Transaction::add( $db );
        $sql = "INSERT OR IGNORE INTO $table (thread,shard,sequence) VALUES (%i, %i, 1)";
        $rs = $db->execute( $sql, $this->thread, $shard );
        if( ! $rs->affected() ){
            $sql = "UPDATE $table SET `sequence` = `sequence` + 1 WHERE `thread` = %i AND `shard` = %i";
            $db->execute( $sql, $this->thread, $shard );
        }
        $sql = "SELECT `sequence` FROM $table WHERE `thread` = %i AND `shard` = %i";
        $rs = $db->execute($sql, $this->thread, $shard);
        $sequence = NULL;
        if( $row = $rs->fetch() ) $sequence = $row['sequence'];
        $rs->free();
        $table = 't_' . $shard;
        $db = $this->db( $table );
        DB\Transaction::start();
        DB\Transaction::add( $db );
        $sql = "INSERT OR IGNORE INTO $table (thread, sequence, data) VALUES (%i, %i, %s)";
        $data = $this->serialize($data);
        $db->execute( $sql, $this->thread, $sequence, $data );
        if( ! $rs->affected() ){
            $sql = "UPDATE $table SET `data` = %s WHERE `thread` = %i AND `sequence` = %i";
            $db->execute( $sql, $data, $this->thread, $sequence );
        }
        DB\Transaction::commit();
        $id = Util::composeId( $shard, $sequence );
        return $id;
    }
    
    public function store( $id, array $data ){
        $ids = Util::validateIds( $this->shardSequences(), array( $id ) );
        if( ! in_array( $id, $ids ) ) throw new Exception('invalid id', $id );
        list( $shard, $sequence ) = Util::parseId( $id );
        $table = 't_' . $shard;
        $db = $this->db( $table );
        $sql = "INSERT OR IGNORE INTO $table (thread, sequence, data) VALUES (%i, %i, %s)";
        $data = $this->serialize($data);
        $rs = $db->execute( $sql, $this->thread, $sequence, $data );
         if( ! $rs->affected() ){
            $sql = "UPDATE $table SET `data` = %s WHERE `thread` = %i AND `sequence` = %i";
            $db->execute( $sql, $data, $this->thread, $sequence );
         }         
        return TRUE;
    }
    
    public function ascending( $limit = 1000, $start_after = NULL ){
        return Util::ascending( $this->shardSequences(), $limit, $start_after );
    }
    
    public function descending( $limit = 1000, $start_after = NULL ){
        return Util::descending( $this->shardSequences(), $limit, $start_after );
    }
    
    public function shardSequences(){
        $table = 't_index';
        $db = $this->db( $table );
        $sql = "SELECT `shard`, `sequence` FROM $table WHERE `thread` = %s ORDER BY `shard` DESC";
        $rs = $db->execute( $sql, $this->thread );
        $result = array();
        while( $row = $rs->fetch() ){
            $result[ $row['shard'] ] = $row['sequence'];
        }
        $rs->free();
        return $result;
    }
    
    public static function dataSchema( $table ){
        return 
        "CREATE TABLE IF NOT EXISTS $table (
          `thread` bigint  NOT NULL,
          `sequence` int NOT NULL,
          `data` text,
          UNIQUE (`thread`, `sequence`)
        )";
    }
    
    public static function indexSchema( $table ){
        return 
        "CREATE TABLE IF NOT EXISTS $table (
          `thread` bigint NOT NULL,
          `shard` int NOT NULL,
          `sequence` int NOT NULL,
          UNIQUE (`thread`, `shard`)
        )";
    }
    
    protected function serialize( $data ){
        return serialize( $data );
    }
    
    protected function unserialize( $string ){
        return unserialize( $string );
    }
    
    protected function db( & $table ){
        $mapper = $this->mapper;
        $db = $mapper( $table );
        if( ! $db instanceof DB ) throw new Exception('invalid db');
        if( ! $db->isa('sqlite') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        return $db;
    }
}
