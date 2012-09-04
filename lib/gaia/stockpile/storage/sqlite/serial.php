<?php
namespace Gaia\Stockpile\Storage\SQLite;
use \Gaia\Stockpile\Exception;
use \Gaia\DB\Transaction;

class Serial extends Core {
    
const TABLE = 'serial';

    
    public function schema(){
        $table = $this->table();
        return 
        "CREATE TABLE IF NOT EXISTS `{$table}` ( " .
        "`user_id` BIGINT NOT NULL, " . 
        "`item_id` INTEGER NOT NULL, " .
        "`serial` BIGINT NOT NULL, " . 
        "`properties` TEXT, " . 
        "`soft_delete` INTEGER NOT NULL DEFAULT '0', " . 
        "UNIQUE (`user_id`,`item_id`,`serial`)); " . 
        "CREATE INDEX IF NOT EXISTS `{$table}_idx_user_id_soft_delete_item_id` ON `{$table}` " . 
        "(`user_id`, `soft_delete`, `item_id`);";
    }
    
    public function add( $item_id, $quantity ){
        $batches = array();
        $local_txn = $this->claimStart();
        $table = $this->table();
        $sql_add =  "INSERT OR IGNORE INTO `{$table}` " . 
                    "(`user_id`, `item_id`, `serial`, `properties`, `soft_delete`) " . 
                    "VALUES (%i, %i, %i, %s, 0)";
        $sql_update =   "UPDATE `{$table}` SET `properties` = %s, `soft_delete` = 0 " . 
                        "WHERE `user_id` = %i AND `item_id` = %i AND `serial` = %i";
        foreach( $quantity->all() as $serial => $properties ){
            $properties = json_encode( $properties );
            $rs = $this->execute($sql_add, $this->user_id, $item_id, $serial, $properties);
            if( $rs->affected() < 1 ) {
                $rs = $this->execute($sql_update, $properties, $this->user_id, $item_id, $serial);
                if( $rs->affected() < 1 ) {
                    if( $local_txn ) Transaction::rollback();
                    throw new Exception('database error', $this->dbInfo() );
                }
            }
        }
        if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        return TRUE;
    }
    
    public function subtract( $item_id, $serials ){
        $table = $this->table();
        $sql =  "UPDATE {$table} SET `soft_delete` = 1 WHERE " .
                "`user_id` = %i AND item_id = %i AND `serial` IN (%i) AND `soft_delete` = 0";
        $rs = $this->execute( $sql, $this->user_id, $item_id, $serials );
        return $rs->affected();
    }
    
    public function fetch( array $item_ids = NULL ){
        $table = $this->table();
        if( is_array( $item_ids ) ) {
            $sql =  "SELECT `item_id`, `serial`, `properties` FROM `{$table}` " .
                    "WHERE `user_id` = %i AND `soft_delete` = 0 AND `item_id` IN( %i )";
            $rs = $this->execute( $sql, $this->user_id, $item_ids );
        } else {
            $sql = "SELECT `item_id`, `serial`, `properties` FROM `{$table}` " .
                    "WHERE `user_id` = %i AND `soft_delete` = 0";
            $rs = $this->execute( $sql, $this->user_id );
        }
        $list = array();
        while( $row = $rs->fetch() ){
            if( ! isset( $list[ $row['item_id'] ] ) ) $list[ $row['item_id'] ] = array();
            $list[ $row['item_id'] ][ $row['serial'] ] = $this->deserializeProperties( $row['properties'] );
        }
        $rs->free();
        return $list;
    }
    
   /**
    * This function may seem to duplicate some logic in fetch, but it is important to separate them out
    * to keep this methodology lightwieght and simply about locking the rows. We want the locking
    * mechanism to be as light as possible. If we have to fetch the properties over the wire, the db
    * has to do more work than it needs to and so does the app. Most likely the properties already came
    * over from the cache in the quantity object earlier.
    */
    public function verifySerials( $item_id, array $serials ){
        $table = $this->table();
        $sql =  "SELECT `serial` FROM `{$table}` " .
                "WHERE `user_id` = %i AND `item_id` = %i AND `serial` IN ( %i )";
        $rs = $this->execute( $sql, $this->user_id, $item_id, $serials );
        $serials = array();
        while( $row = $rs->fetch() )  $serials[] = $row['serial'];
        $rs->free();
        return $serials;
    }
    
   /**
    * deserialize the properties stored in the db.
    * if there is any string in the db at all, we assume it is a json string.
    * otherwise, return an empty array.
    */
    protected function deserializeProperties( $v ){
        if (strlen( $v ) < 0 ) return array();
        $v = @json_decode( $v, TRUE );
        if( ! is_array( $v ) )  return array();
        return $v;
    }
    
} // EOC

