<?php
namespace Gaia\NewID\DB;
use Gaia\NewID\Iface;
use Gaia\Exception;
use Gaia\StorageIface;
use Gaia\Store;

class MySQL implements Iface {
    
    const WIGGLE = 10;
    static $VERSION = 1;
    static $create_table = 
            "CREATE TABLE IF NOT EXISTS {TABLE} (
                  `id` int UNSIGNED NOT NULL,
                  `counter` bigint UNSIGNED NOT NULL,
                  PRIMARY KEY  (`id`)
                ) ENGINE=InnoDB";
    protected $app;
    protected $db;
    protected static $info = array();
    
    public function __construct( \Gaia\DB $db, $app = 'default' ){
        if( ! preg_match('/^[a-z0-9_]+$/', $app) ) throw new Exception('invalid-app');
        if( ! $db->isa('mysql')){
            trigger_error('invalid db layer', E_USER_ERROR);
            exit(1);
        }
        if( ! $db->isa('gaia\db\except') ) $db = new \Gaia\DB\Except( $db );
        $this->app = $app;
        $this->db = $db;
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
        $this->db->execute( str_replace('{TABLE}', $table, self::$create_table ) );
    }
    
    public function testInit(){
        $this->db->execute( str_replace('CREATE TABLE', 'CREATE TEMPORARY TABLE', str_replace('{TABLE}', $this->table(), self::$create_table ) ) );
    }
    
    protected function table(){
        return 'newid_' . $this->app;
    }
    
    protected function _incrementCounter($offset, $ct){
        $sql = 'INSERT INTO `%s` ( `id`, `counter` ) VALUES ( %d, @COUNTER := %s ) ON DUPLICATE KEY UPDATE `counter` = @COUNTER:=( `counter` + %s )';
        $this->db->execute( sprintf( $sql, $this->table(), $offset, ($ct + $offset), $ct ) );
        $rs = $this->db->execute('SELECT @COUNTER as ct');
        $row = $rs->fetch();
        if ($row == false) throw new Exception("No sequence returned from counter.");      
        return $row['ct'];
    }
    
    // put this on a cron to check periodically, once a day is good.
    public function resetCounterToMax(){
        $info = $this->_offsetInfo();
        $diff = $this->_diffCounterFromMax( $info['offset'] );
        if( $diff > self::WIGGLE ){
            $rs = $this->_incrementCounter( $info['offset'], ( ceil( ( $diff + 1) / $info['increment']) * $info['increment'] ) );
        }
    }
    
    protected function _offsetInfo(){
        if( isset( self::$info[ $this->app ] ) ) return self::$info[ $this->app ];
        $rs = $this->db->execute('SELECT @@auto_increment_increment as increment, @@auto_increment_offset as offset');
        $info = $rs->fetch();
        if( ! $this->_validate_offsetInfo( $info ) ) throw new Exception('invalid-offset-info');
        return self::$info[ $this->app ] = $info;
    }
    
    protected function _diffCounterFromMax( $row_id ){
        $rs = $this->db->execute('SELECT * FROM ' . $this->table());
        $max = 0; $mine = 0;
        while( $row = $rs->fetch() ){
            if( $row['id'] == $row_id ) {
                $mine = $row['counter'];
            } else {
                if( $row['counter'] > $max ) $max = $row['counter'];
            }
        }
        $rs->free();
        return $diff = bcsub( $max, $mine);
    }
    
    protected function _resetCounterToMax( array $info ){
        
    }

    protected function _validate_offsetInfo( $row ) {
        if( ! is_array( $row ) ) return FALSE;
        if( ! isset( $row['offset'] ) ) return FALSE;
        if( ! isset( $row['increment'] ) ) return FALSE;
        if( ! ctype_digit( $row['offset'] ) ) return FALSE;
        if( ! ctype_digit( $row['increment'] ) ) return FALSE;
        return $row;
    }
    
}