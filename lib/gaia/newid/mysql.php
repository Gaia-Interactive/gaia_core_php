<?php
namespace Gaia\NewID;
use Gaia\Exception;
use Gaia\Cache;

abstract class MySQL implements Iface {
    
    const WIGGLE = 10;
    static $VERSION = 1;
    static $offsetinfo = array();
    static $create_table = 
            "CREATE TABLE IF NOT EXISTS {TABLE} (
                  `id` int UNSIGNED NOT NULL,
                  `counter` bigint UNSIGNED NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB";
    protected $app;
    protected $db;
    protected $cache;
    
    public function __construct( $db, Cache\Iface $cache, $app ){
        $this->db = $db;
        if( $app ) $this->app = $app;
        if( ! preg_match('/^[a-z0-9_]+$/', $app) ) throw new Exception('invalid-app');
        $this->app = $app; 
        $this->cache = new Cache\Prefix( $cache, __CLASS__ . '/' . $app);
    }
    
    public function id(){
        $ids = $this->ids( 1 );
        return array_pop( $ids );
    }
    
    public function ids( $ct = 1 ){
         if( $ct < 1 ) $ct = 1;
        if ( $ct > 20000) {
            throw new Exception("Invalid number to generate. Number is $ct.");
        }
        $info = $this->_offsetInfo();
        $ct = intval($ct) * $info['increment'];
        $lastId = $this->_incrementCounter( $info['offset'], $ct );              
        $remain = $ct;
        $list = array();
        while ($remain > 0 ) {
            $list[] = bcsub($lastId, $remain);
            $remain -= $info['increment'];
        }
        return $list;
    }


    public function init(){
        $key = 'createtable';
        if( $this->cache->get($key )) return;
        if( ! $this->cache->add($key, 1, 5)) return;
        $table = $this->table();
        $rs = $this->execute('SHOW TABLES LIKE %s', $table);
        if( ! $this->fetch_assoc( $rs ) ) $this->query( str_replace('{TABLE}', $table, self::$create_table ) );
        $this->cache->set($key, 1, 60);
    }
    
    public function testInit(){
        $this->query( str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', str_replace('{TABLE}', $this->table(), self::$create_table ) ) );
    }
    
    protected function table(){
        return 'newid_' . $this->app;
    }
    
    protected function _incrementCounter($offset, $ct){
        $sql = 'INSERT INTO `%s` ( `id`, `counter` ) VALUES ( %d, @COUNTER := %s ) ON DUPLICATE KEY UPDATE `counter` = @COUNTER:=( `counter` + %s )';
        $this->query( sprintf( $sql, $this->table(), $offset, ($ct + $offset), $ct ) );
        $rs = $this->query('SELECT @COUNTER as ct');
        $row = $this->fetch_assoc($rs);
        if ($row == false) throw new Exception("No sequence returned from counter.");      
        return $row['ct'];
    }
    
    public function resetCounterToMax(){
        return $this->_resetCounterToMax( $this->_offsetInfo() );
    }
    
    protected function _offsetInfo(){
        $dsn = $this->dbinfo();
        if( isset( self::$offsetinfo[ $dsn ] ) ) return self::$offsetinfo[ $dsn ];
        $checksum = __CLASS__  . 'settings/v' . self::$VERSION .'/' . md5( $dsn );
        $row = $this->cache->get($checksum);
        if( $this->_validate_offsetInfo( $row ) ) return self::$offsetinfo[ $dsn ] = $row;
        $rs = $this->query('SELECT @@auto_increment_increment as increment, @@auto_increment_offset as offset');
        $info = $this->fetch_assoc($rs);
        if( ! $this->_validate_offsetInfo( $info ) ) throw new Exception('invalid-offset-info');
        $this->_resetCounterToMax( $info );
        $this->cache->set( $checksum, $info, 0, 300);
        return self::$offsetinfo[ $dsn ] = $info;
    }
    
    protected function _diffCounterFromMax( $row_id ){
        $rs = $this->query('SELECT * FROM ' . $this->table());
        $max = 0; $mine = 0;
        while( $row = $this->fetch_assoc($rs) ){
            if( $row['id'] == $row_id ) {
                $mine = $row['counter'];
            } else {
                if( $row['counter'] > $max ) $max = $row['counter'];
            }
        }
        $this->free( $rs );
        return $diff = bcsub( $max, $mine);
    }
    
    protected function _resetCounterToMax( array $info ){
        $checksum = '/resetmax/locker/';
        if( ! $this->cache->add( $checksum, 1, 0, 15) && $this->cache->get( $checksum ) ) return;
        $diff = $this->_diffCounterFromMax( $info['offset'] );
        if( $diff > self::WIGGLE ){
            $rs = $this->_incrementCounter( $info['offset'], ( ceil( ( $diff + 1) / $info['increment']) * $info['increment'] ) );
        }
        $this->cache->delete( $checksum );
    }

    protected function _validate_offsetInfo( $row ) {
        if( ! is_array( $row ) ) return FALSE;
        if( ! isset( $row['offset'] ) ) return FALSE;
        if( ! isset( $row['increment'] ) ) return FALSE;
        if( ! ctype_digit( $row['offset'] ) ) return FALSE;
        if( ! ctype_digit( $row['increment'] ) ) return FALSE;
        return $row;
    }
    
    protected function query( $sql ){
         $rs = $this->db->query($sql);
        if( ! $rs )throw new Exception('database-error', $this->db);
        return $rs;
    }

    abstract protected function fetch_assoc( $rs );
    abstract protected function dbinfo();
    abstract protected function free( $rs );
    
}