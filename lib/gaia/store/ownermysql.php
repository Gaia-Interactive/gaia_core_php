<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;
use Gaia\Time;
use Gaia\DB;
use Gaia\Exception;

// basic wrapper to make mysql library conform to the storage interface.
class OwnerMySQL implements Iface {
    
    /**
    * pluggable serializer 
    */
    protected $s;
    
   /**
    * db object or dsn string
    */
    protected $db;
    
    protected $owner;
    
    /*
    * table
    */
    protected $table;
    
   /**
    * create the mysql object.
    * owner is an integer that you use as the first part of the unique key name. Often when
    * composing a key for memcache data a user id, or thread id is used in the key name.
    * if you pass this parameter to the constructor instead like you might to the prefix class,
    * you can get much greater performance.
    * 
    * the db var can either be a gaia\db\iface object, or a dsn string that resolves to one
    * when passed to DB\Connection::instance()
    * This allows you to bypass the connection class if you want to, but avoids having to connect
    * to mysql unnecessarily if you embed this class somewhere in your infrastructure but never 
    * invoke any methods.
    *
    * The serialize param is for changing out the serialization mechanism. Can use json if that is
    * a better fit.
    */
    public function __construct($owner, $db, $table, \Gaia\Serialize\Iface $s = NULL ){
        $owner = strval( $owner );
        if( ! ctype_digit( $owner ) ) throw new Exception('invalid owner', $owner );
        $this->owner = $owner;
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
        $this->s = ( $s ) ? $s : new \Gaia\Serialize\PHP;
    }
    
    /**
    * standard get method. wrapper for the getMulti method.
    */
    public function get( $request){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return NULL;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return NULL;
        return $res[ $request ];
    }

    /**
    * easier to program for a list of keys passed in and returned, than the overloaded interface 
    * of the normal get method.
    */
    protected function getMulti( array $request ){
        $keys = array();
        foreach( $request as $k ){
            $keys[ sha1($k, TRUE) ] = $k;
        }
        $now = $this->now();
        $rows = array();
        $rows = array_fill_keys($request, NULL);
        $query = "SELECT `id`, `data` FROM {$this->table} WHERE `id` IN (%s) AND `ttl` >= %i";
        $rs = $this->db()->execute( $query, array_keys( $keys ), $now );
        while( $row = $rs->fetch() ) {
            if( ! isset( $keys[ $row['id'] ] ) ) {
                continue;
            }
            $rows[ $keys[ $row['id'] ] ] = $this->unserialize( $row['data'] );
        }
        $rs->free(); 
        
        foreach( $rows as $k => $v){
            if( $v === NULL ) unset( $rows[ $k ] );
        }
        return $rows;
      
    }
    
   /**
    * add a key
    */
    public function add( $k, $v, $ttl = NULL ){
        $now = $this->now();
        $ttl = $this->ttl( $ttl );
        $rs = $this->db()->execute("UPDATE `{$this->table}` SET `data` = %s, `ttl` = %i, `revision` = `revision` + 1 WHERE `owner` = %i AND `id` = %s AND `ttl` < %i", $v, $ttl, $this->owner, sha1($k, TRUE), $now);
        if( $rs->affected() > 0 ) return TRUE;
        $rs = $this->db()->execute("INSERT IGNORE INTO `{$this->table}` (`owner`, `id`, `keyname`, `data`, `ttl`, `revision`) VALUES (%i, %s, %s, %s, %i, 1)", $this->owner, sha1($k, TRUE), $k, $this->serialize($v), $ttl);
        return $rs->affected() > 0;
    }

   /**
    * set a key
    */
    public function set( $k, $v, $ttl = NULL ){
        if( $v === NULL ) return $this->delete( $k );
        $now = $this->now();
        $ttl = $this->ttl( $ttl );
        $rs = $this->db()->execute("INSERT INTO `{$this->table}` (`owner`, `id`, `keyname`, `data`, `ttl`, `revision`) VALUES (%i, %s, %s, %s, %i, 1) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `ttl` = VALUES(`ttl`), `revision` = `revision` + 1", $this->owner, sha1($k, TRUE), $k, $this->serialize($v), $ttl);
        return $rs->affected() > 0;
    }

   /**
    * replace a key
    */
    public function replace( $k, $v, $ttl = NULL ){
        $now = $this->now();
        $ttl = $this->ttl( $ttl );
        $rs = $this->db()->execute("UPDATE `{$this->table}` SET `data` = %s, ttl = %i, `revision` = `revision` + 1 WHERE `owner` = %i AND `id` = %s AND `ttl` >= %i", $v, $ttl, $this->owner, sha1($k, TRUE), $now);
        return $rs->affected() > 0;
    }

   /**
    * replace a key
    */
    public function increment( $k, $v = 1 ){
        $now = $this->now();
        $this->db()->execute("UPDATE `{$this->table}` SET `data` =  @TOTAL:=CAST(`data` AS UNSIGNED) + %i, `revision` = `revision` + 1 WHERE `owner` = %i AND `id` = %s AND `ttl` >= %i", $v, $this->owner, sha1($k, TRUE), $now);
        $rs = $this->db()->execute('SELECT @TOTAL as total');
        $row = $rs->fetch();
        $rs->free();
        return $row['total'];
    }

   /**
    * decrement a key
    */
    public function decrement( $k, $v = 1 ){
        $now = $this->now();
        $rs = $this->db()->execute("UPDATE `{$this->table}` SET `data` =  @TOTAL:=CAST(`data` AS UNSIGNED) - %i, `revision` = `revision` + 1 WHERE `owner` = %i AND `id` = %s AND `ttl` >= %i", $v, $this->owner, sha1($k, TRUE), $now);
        $rs = $this->db()->execute('SELECT @TOTAL as total');
        $row = $rs->fetch();
        $rs->free();
        return $row['total'];
    }

   /**
    * delete a key
    */
    public function delete( $k ){
        $rs = $this->db()->execute("UPDATE `{$this->table}` SET `data`= NULL, ttl = NULL, `revision` = `revision` + 1 WHERE `owner` = %i AND `id` IN( %s )", $this->owner, sha1($k, TRUE));
        return TRUE;
    }
    
    protected function now(){
        return Time::now();
    }

    public function initialize(){
        $this->db()->execute($this->schema());
    }
    
    public function schema(){
        return 
        "CREATE TABLE IF NOT EXISTS `{$this->table}` (" .
            "`rowid` BIGINT UNSIGNED NOT NULL  AUTO_INCREMENT PRIMARY KEY, " . 
            "`owner` BIGINT UNSIGNED NOT NULL, " .
            "`id` binary(20) NOT NULL, " . 
            "`keyname` varchar(500) NOT NULL, " . 
            "`data` BLOB, " .
            "`ttl` INT UNSIGNED NOT NULL, " . 
            "`revision` BIGINT UNSIGNED NOT NULL, " .
            "UNIQUE `owner_id` (`owner`,`id`), " .
            "INDEX `ttl` (`ttl`) " .
            ") Engine=InnoDB";
    }
    
    public function flush(){
        $this->db()->execute("DELETE FROM {$this->table} WHERE `owner` = %i", $this->owner);
    }
    
    public function ttlEnabled(){
        return TRUE;
    }
    
    public function load( $input ){
        if( $input === NULL ) return;
        if( is_array( $input ) || $input instanceof Iterator ) {
            foreach( $input as $k=>$v ) $this->__set( $k, $v);
        }
    }
    
    public function __set( $k, $v ){
        if( ! $this->set( $k, $v ) ) return FALSE;
        return $v;
    }
    public function __get( $k ){
        return $this->get( $k );
    }
    public function __unset( $k ){
        return $this->delete( $k );
    }
    public function __isset( $k ){
        $v = $this->get( $k );
        if( $v === FALSE || $v === NULL ) return FALSE;
        return TRUE;
    }
    
    protected function serialize($v){
        return $this->s->serialize($v);
    }
    
    protected function unserialize( $v ){
        return $this->s->unserialize($v);
    }
    
    protected function ttl( $ttl ){
        if( $ttl < 1 ){
            return '4294967295';
        } elseif( $ttl < Time::now() + Wrap::TTL_30_DAYS ) {
            return Time::now() + $ttl;
        }
    }
    
    protected function db(){
        $closure = $this->db;
        $db = $closure();
        if( DB\Transaction::inProgress() ) DB\Transaction::add( $db );
        return $db;
    }
}