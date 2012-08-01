<?php
namespace Gaia\Skein;
use Gaia\DB;
use Gaia\Exception;

// mysql implementation of skein.
class MySQL implements Iface {
    
    protected $db;
    protected $thread;
    protected $table_prefix;
    
    /**
    * Thread is an integer id that your thread of entries will be tied to.
    * For db, you can pass in:
    *       a closure that will accept the table name return the db
    *       a db\iface object
    *       a dsn string that will be passed to db\connection::instance to create the db object
    *  Table prefix is an optional string that will allow you to prefix your table names with
    * a custom string. If you pass in nothing, you will get back table names like:
    *       skein_index
    *       skein_201207
    * if you were to pass in 'test', you would get names like:
    *       testskein_index
    *       testskein_201207
    */
    public function __construct( $thread, $db, $table_prefix = '' ){
        $this->db = $db;
        $this->thread = $thread;
        $this->table_prefix = $table_prefix;
    }
    
    /**
    * count how many entries are in the thread.
    */
    public function count(){        
        $table = $this->table('index');
        $db = $this->db( $table );
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "SELECT SUM( `sequence` ) as ct FROM $table WHERE `thread` = %s";
        $rs = $db->execute( $sql, $this->thread );
        $result = 0;
        if( $row = $rs->fetch() ){
            $result = $row['ct'];
        }
        $rs->free();
        return $result;
    }
    
    /**
    * fetch by id. can either be a single id, or a list of them.
    */
    public function get( $id ){
        if( is_array( $id ) ) return $this->multiget( $id );
        $res = $this->multiget( array( $id ) );
        return isset( $res[ $id ] ) ? $res[ $id ] : NULL;
    }
    
    /**
    * actual logic for retrieving data.
    */
    protected function multiGet( array $ids ){
        $result = array_fill_keys( $ids, NULL);
        foreach( Util::parseIds( $ids ) as $shard=>$sequences ){
            $table = $this->table($shard);
            $db = $this->db( $table );
            if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
            $sql = "SELECT `sequence`,`data` FROM `$table` WHERE `thread` = %s AND `sequence` IN( %i )";
            $rs = $db->execute( $sql, $this->thread, $sequences );
            while( $row = $rs->fetch() ){
                $id = Util::composeId( $shard, $row['sequence'] );
                $result[ $id ] = $this->unserialize($row['data']);
            }
            $rs->free();
        }
        foreach( array_keys( $result, NULL, TRUE) as $rm ) unset( $result[ $rm ] );
        return $result;
    }
    
    /**
    * add a new entry to the skein. returns the id.
    */
    public function add( $data, $shard = NULL ){
        $shard = strval($shard);
        if( ! ctype_digit( $shard ) ) $shard = Util::currentShard();
        $table = $this->table('index');
        $dbi = $this->db($table);
        DB\Transaction::start();
        $dbi->start();
        $sql = "INSERT INTO $table (thread,shard,sequence) VALUES (%i, %i, @SKEIN_SEQUENCE:=1) ON DUPLICATE KEY UPDATE `sequence` = @SKEIN_SEQUENCE:=( `sequence` + 1 )";
        $dbi->execute( $sql, $this->thread, $shard );
        $rs = $dbi->execute('SELECT @SKEIN_SEQUENCE as sequence');
        $sequence = NULL;
        if( $row = $rs->fetch() ) $sequence = $row['sequence'];
        $rs->free();
        $table = $this->table($shard);
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
    
    /**
    * update an existing entry based on id. It does an insert or update just in case the record is missing in the db.
    */
    public function store( $id, $data ){
        $ids = Util::validateIds( $this->shardSequences(), array( $id ) );
        if( ! in_array( $id, $ids ) ) throw new Exception('invalid id', $id );
        list( $shard, $sequence ) = Util::parseId( $id );
        $table = $this->table($shard);
        $db = $this->db( $table );
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "INSERT INTO $table (thread, sequence, data) VALUES (%i, %i, %s) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`)";
        $db->execute( $sql, $this->thread, $sequence, $this->serialize($data) );
        return TRUE;
    }
    
    /*
    * get a list of ids in ascending/descending order starting after a given id
    */
    public function ids( array $params = array() ){
        return Util::ids( $this->shardSequences(), $params );
    }

   /**
    * iterate through every record
    * and pass the results to a closure.
    * if the closure returns FALSE, it breaks out of the loop.
    */
    public function filter( array $params ){
        Util::filter( $this, $params );
    }

    
   /**
    * Utility function used mainly by other functions to derive values, but can be used by
    * the application if you know what you are doing.
    * Returns a count of how many entries are in each shard.
    */
    public function shardSequences(){
        $table = $this->table('index');
        $db = $this->db( $table );
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        $sql = "SELECT `shard`, `sequence` FROM $table WHERE `thread` = %s ORDER BY `shard` DESC";
        $rs = $db->execute( $sql, $this->thread );
        $result = array();
        while( $row = $rs->fetch() ){
            $result[ $row['shard'] ] = $row['sequence'];
        }
        $rs->free();
        return $result;
    }
    
   /**
    * given a table name, return the shema for the data shard table.
    * Allows you to programmatically create your table, either in the constructor closure,
    * or in an admin script or cron.
    */
    public static function dataSchema( $table ){
        return 
        "CREATE TABLE IF NOT EXISTS $table (
          `thread` bigint unsigned NOT NULL,
          `sequence` int unsigned NOT NULL,
          `data` text character set utf8,
          UNIQUE KEY (`thread`, `sequence`)
        ) ENGINE=InnoDB";
    }
    
        
   /**
    * given a table name, return the shema for the index table.
    * Allows you to programmatically create your table, either in the constructor closure,
    * or in an admin script or cron.
    */
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
    
    protected function table( $suffix ){
        return $this->table_prefix . 'skein_' . $suffix;
    }
    
    protected function db( $table ){
        if( $this->db instanceof \Closure ){
            $mapper = $this->db;
            $db = $mapper( $table );
        } elseif( is_scalar( $this->db ) ){
            $db = DB\Connection::instance( $this->db );
        } else {
            $db = $this->db;
        }
        if( ! $db instanceof DB\Iface ) throw new Exception('invalid db');
        if( ! $db->isa('mysql') ) throw new Exception('invalid db');
        if( ! $db->isa('Gaia\DB\Except') ) $db = new DB\Except( $db );
        return $db;
    }
}
