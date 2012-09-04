<?php
namespace Gaia\Stockpile\Storage\SQLITE;
use \Gaia\Stockpile\Exception;
use \Gaia\DB\Transaction;

/**
 * base class for sorting.
 * Has a method that allows the app to pass in a sorted list of item ids, and those ids will
 * be added to the top of the list in the sort.
 * We can add other functions too if we find them useful.
 */
class Sorter extends Core {

const TABLE = 'sort';

    public function schema(){
        $table = $this->table();
        return "CREATE TABLE IF NOT EXISTS `{$table}` ( " . 
                "`user_id` INTEGER NOT NULL, " .
                "`item_id` INTEGER NOT NULL, " .
                "`pos` INTEGER NOT NULL default '0', " .
                "UNIQUE  (`user_id`,`item_id`));" . 
                
                "CREATE INDEX IF NOT EXISTS `{$table}_idx_user_id_pos` " . 
                "ON `{$table}` ( `user_id`, `pos`);";
    }
    
    
    public function sort( $pos, array $item_ids, $ignore_dupes = FALSE ){
        $batch = array();
        $ct = 0;
        $local_txn = $this->claimStart();
        $table = $this->table();
        $sql_insert = "INSERT OR IGNORE INTO `{$table}` (`user_id`, `item_id`, `pos`) VALUES (%i, %i, %i)";
        $sql_update = "UPDATE `{$table}` SET `pos` = %i WHERE `user_id` = %i AND `item_id` = %i";
        foreach( $item_ids as $item_id ){
            $pos = bcadd($pos, 1);
            $rs = $this->execute($sql_insert, $this->user_id, $item_id, $pos );
            $ct += $curr = $rs->affected();
            if( ! $ignore_dupes && ! $curr  ){
                $rs = $this->execute($sql_update, $pos, $this->user_id, $item_id );
                $ct += $rs->affected();
            }
        }
         if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        return $ct;
    }
    
    public function remove( $item_id ){
        $table = $this->table();
        $sql = "UPDATE `{$table}` SET `pos` = 0 WHERE `user_id` = %i AND `item_id` = %i";
        $rs = $this->execute( $sql, $this->user_id, $item_id );
    }
    
   /**
    * get the position for a list of item ids. triggered by the cache callback in FETCH.
    * @returns the positions, keyed by item id.
    */
    public function fetchPos( array $ids ){
        $table = $this->table();
        $sql = "SELECT `item_id`, `pos` FROM `{$table}` WHERE `user_id` = %i AND `item_id` IN ( %i )";
        $rs = $this->db->execute($sql, $this->user_id, $ids );
        $list = array();
        while( $row = $rs->fetch() ){
            $list[ $row['item_id'] ] = $row['pos'];
        }
        $rs->free();
        return $list;
    }
    
   /**
    * what is the largest position number we have in our sort list?
    */
    public function maxPos(){
        $table = $this->table();
        $sql = "SELECT MAX(`pos`) as `pos` FROM `{$table}` WHERE `user_id` = %i";
        $rs = $this->execute($sql, $this->user_id );
        $row = $rs->fetch();
        $rs->free();
        return $row['pos'];
    }
 
} // EOC


