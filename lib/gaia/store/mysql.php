<?php
/**
 * @copyright 2003-present GAIA Interactive, Inc.
 */
namespace Gaia\Store;
use Gaia\Time;
use Gaia\DB;

// basic wrapper to make redis library conform to the cache interface.
// todo: figure out ways to make some of the more elegant list and member set functionality 
// of redis available through the wrapper interface without breaking things.
class MySQL implements Iface {
    
    protected $s;
    protected $resolver;
    
    public function __construct(\Closure $resolver, \Gaia\Serialize\Iface $s = NULL ){
        $this->resolver = $resolver;
        $this->s = ( $s ) ? $s : new \Gaia\Serialize\PHP;
    }
    
    public function get( $request){
        if( is_array( $request ) ) return $this->getMulti( $request );
        if( ! is_scalar( $request ) ) return NULL;
        $res = $this->getMulti( array( $request ) );
        if( ! isset( $res[ $request ] ) ) return NULL;
        return $res[ $request ];
    }

    protected function getMulti( array $request ){
        $conns = array();
        foreach( $request as $k ){
            list( $connstring, $table ) =  $this->hash( $k );
            if( ! isset( $conns[ $connstring ] ) ) $conns[ $connstring ] = array();
            if( ! isset( $conns[ $connstring ][ $table ] ) ) $conns[ $connstring ][ $table ] = array();
            $conns[ $connstring ][ $table ][ sha1($k, TRUE) ] = $k;
        }
        $now = $this->now();
        $rows = array();
        $rows = array_fill_keys($request, NULL);
        foreach( $conns as $connstring => $tablelist ){
            $db = $this->db($connstring);
            foreach( $tablelist as $table => $keys ){
                $query = "SELECT `id`, `data` FROM {$table} WHERE `id` IN (%s) AND `ttl` >= %i";
                $rs = $db->execute( $query, array_keys( $keys ), $now );
                while( $row = $rs->fetch() ) {
                    if( ! isset( $keys[ $row['id'] ] ) ) {
                        continue;
                    }
                    $rows[ $keys[ $row['id'] ] ] = $this->unserialize( $row['data'] );
                }
                $rs->free(); 
            }
        }
        foreach( $rows as $k => $v){
            if( $v === NULL ) unset( $rows[ $k ] );
        }
        return $rows;
      
    }
    
    public function add( $k, $v, $ttl = NULL ){
        list( $connstring, $table ) =  $this->hash( $k );
        $db = $this->db( $connstring );
        $now = $this->now();
        $ttl = $this->ttl( $ttl );
        $rs = $db->execute("UPDATE `{$table}` SET `data` = %s, `ttl` = %i, `revision` = `revision` + 1 WHERE `id` = %s AND `ttl` < %i", $v, $ttl, sha1($k, TRUE), $now);
        if( $rs->affected() > 0 ) return TRUE;
        $rs = $db->execute("INSERT IGNORE INTO `{$table}` (`id`, `keyname`, `data`, `ttl`, `revision`) VALUES (%s, %s, %s, %i, 1)", sha1($k, TRUE), $k, $this->serialize($v), $ttl);
        return $rs->affected() > 0;
    }
    
    public function set( $k, $v, $ttl = NULL ){
        if( $v === NULL ) return $this->delete( $k );
        list( $connstring, $table ) =  $this->hash( $k );
        $db = $this->db( $connstring );
        $now = $this->now();
        $ttl = $this->ttl( $ttl );
        $rs = $db->execute("INSERT INTO `{$table}` (`id`, `keyname`, `data`, `ttl`, `revision`) VALUES (%s, %s, %s, %i, 1) ON DUPLICATE KEY UPDATE `data` = VALUES(`data`), `ttl` = VALUES(`ttl`), `revision` = `revision` + 1", sha1($k, TRUE), $k, $this->serialize($v), $ttl);
        return $rs->affected() > 0;
    }
    
    public function replace( $k, $v, $ttl = NULL ){
        list( $connstring, $table ) =  $this->hash( $k );
        $db = $this->db( $connstring );
        $now = $this->now();
        $ttl = $this->ttl( $ttl );
        $rs = $db->execute("UPDATE `{$table}` SET `data` = %s, ttl = %i, `revision` = `revision` + 1 WHERE id = %s AND `ttl` >= %i", $v, $ttl, sha1($k, TRUE), $now);
        return $rs->affected() > 0;
    }
    
    public function increment( $k, $v = 1 ){
        list( $connstring, $table ) =  $this->hash( $k );
        $db = $this->db( $connstring );       
        $now = $this->now();
        $db->execute("UPDATE `{$table}` SET `data` =  @TOTAL:=CAST(`data` AS UNSIGNED) + %i, `revision` = `revision` + 1 WHERE id = %s AND ttl >= %i", $v, sha1($k, TRUE), $now);
        $rs = $db->execute('SELECT @TOTAL as total');
        $row = $rs->fetch();
        $rs->free();
        return $row['total'];
    }
    
    public function decrement( $k, $v = 1 ){
        list( $connstring, $table ) =  $this->hash( $k );
        $db = $this->db( $connstring );
        $now = $this->now();
        $rs = $db->execute("UPDATE `{$table}` SET `data` =  @TOTAL:=CAST(`data` AS UNSIGNED) - %i, `revision` = `revision` + 1 WHERE id = %s AND `ttl` >= %i", $v, sha1($k, TRUE), $now);
        $rs = $db->execute('SELECT @TOTAL as total');
        $row = $rs->fetch();
        $rs->free();
        return $row['total'];
    }
    
    public function delete( $k ){
        list( $connstring, $table ) =  $this->hash( $k );
        $db = $this->db( $connstring );        
        $rs = $db->execute("UPDATE `{$table}` SET `data`= NULL, ttl = NULL, `revision` = `revision` + 1 WHERE id IN( %s )", sha1($k, TRUE));
        return TRUE;
    }
    
    protected function now(){
        return Time::now();
    }
    
    protected function hash( $key ){
        $closure = $this->resolver;
        return $closure( $key );
    }
    
    protected function db( $connstring ){
        $db = DB\Connection::instance( $connstring );
        if( ! $db->isa('mysql') ) throw new Exception('invalid driver', $db );
        if( ! $db->isa('Gaia\DB\Except') ) $db = new \Gaia\DB\Except( $db );
        return $db;
    }
    
    public static function initializeStatement( $table ){
        return "CREATE TABLE IF NOT EXISTS `{$table}` (`rowid` BIGINT UNSIGNED NOT NULL  AUTO_INCREMENT PRIMARY KEY, `id` binary(20) NOT NULL, `keyname` varchar(500) NOT NULL, `data` varbinary(64000), `ttl` INT UNSIGNED NOT NULL, revision BIGINT UNSIGNED NOT NULL, UNIQUE `id` (`id`), INDEX `ttl` (`ttl`) ) Engine=InnoDB";
    }
    
    public function flush(){
        throw new Exception( __CLASS__ . '::' . __FUNCTION__ . ' not implemented');
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
}