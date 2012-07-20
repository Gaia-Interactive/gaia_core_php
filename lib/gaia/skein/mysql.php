<?php
namespace Gaia\Skein;
use Gaia\DB;
use Gaia\Exception;

class MySQL implements Iface {
    
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
        $result = array_fill_keys( $ids, NULL);
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
        foreach( array_keys( $result, NULL, TRUE) as $rm ) unset( $result[ $rm ] );
        return $result;
    }
    
    
    public function add( $data, $shard = NULL ){
        $shard = strval($shard);
        if( ! ctype_digit( $shard ) ) $shard = Util::currentShard();
        $table = 't_index';
        $dbi = $this->db($table);
        DB\Transaction::start();
        $dbi->start();
        $sql = "INSERT INTO $table (thread,shard,sequence) VALUES (%i, %i, @SKEIN_SEQUENCE:=1) ON DUPLICATE KEY UPDATE `sequence` = @SKEIN_SEQUENCE:=( `sequence` + 1 )";
        $dbi->execute( $sql, $this->thread, $shard );
        $rs = $dbi->execute('SELECT @SKEIN_SEQUENCE as sequence');
        $sequence = NULL;
        if( $row = $rs->fetch() ) $sequence = $row['sequence'];
        $rs->free();
        $table = 't_' . $shard;
        $dbs = $this->db( $table );
        $dbs->start();
        $sql = "INSERT INTO $table (thread, sequence, data) VALUES (%i, %i, %s) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)";
        $dbs->execute( $sql, $this->thread, $sequence, $this->serialize($data) );
        $dbi->commit();
        $dbs->commit();
        DB\Transaction::commit();
        $id = Util::composeId( $shard, $sequence );
       
        return $id;
    }
    
    public function store( $id, $data ){
        $ids = Util::validateIds( $this->shardSequences(), array( $id ) );
        if( ! in_array( $id, $ids ) ) throw new Exception('invalid id', $id );
        list( $shard, $sequence ) = Util::parseId( $id );
        $table = 't_' . $shard;
        $db = $this->db( $table );
        $sql = "INSERT INTO $table (thread, sequence, data) VALUES (%i, %i, %s) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)";
        $db->execute( $sql, $this->thread, $sequence, $this->serialize($data) );
        return TRUE;
    }
    
    public function ascending( $limit = 1000, $start_after = NULL ){
        return Util::ascending( $this->shardSequences(), $limit, $start_after );
    }
    
    public function descending( $limit = 1000, $start_after = NULL ){
        return Util::descending( $this->shardSequences(), $limit, $start_after );
    }

    public function filterAscending( \Closure $c, $start_after = NULL ){
        Util::filter( $this, $c, 'ascending', $start_after );
    }
    
    public function filterDescending( \Closure $c, $start_after = NULL ){
        Util::filter( $this, $c, 'descending', $start_after );
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
          `thread` bigint unsigned NOT NULL,
          `sequence` int unsigned NOT NULL,
          `data` text character set utf8,
          UNIQUE KEY (`thread`, `sequence`)
        ) ENGINE=InnoDB";
    }
    
    public static function indexSchema( $table ){
        return 
        "CREATE TABLE IF NOT EXISTS $table (
          `thread` bigint unsigned NOT NULL,
          `shard` int unsigned NOT NULL,
          `sequence` int unsigned NOT NULL,
          UNIQUE KEY (`thread`, `shard`)
        ) ENGINE=InnoDB";
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
        if( ! $db instanceof DB\Iface ) throw new Exception('invalid db');
        if( ! $db->isa('mysql') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        return $db;
    }
}
