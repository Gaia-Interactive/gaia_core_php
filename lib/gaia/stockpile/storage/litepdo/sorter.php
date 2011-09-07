<?php
namespace Gaia\Stockpile\Storage\LitePDO;
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
    
const SQL_CREATE =
"CREATE TABLE IF NOT EXISTS `{TABLE}` (
  `user_id` INTEGER NOT NULL,
  `item_id` INTEGER NOT NULL,
  `pos` INTEGER NOT NULL default '0',
  UNIQUE  (`user_id`,`item_id`)
)";

const SQL_INDEX =
"CREATE INDEX IF NOT EXISTS `{TABLE)_idx_user_id_pos` ON `{TABLE}` ( `user_id`, `pos`)";

const SQL_SELECT = 
    'SELECT `item_id`, `pos` FROM `{TABLE}` WHERE `user_id` = %i AND `item_id` IN ( %i )';

const SQL_UPDATE =
'UPDATE `{TABLE}` SET `pos` = %i WHERE `user_id` = %i AND `item_id` = %i';

const SQL_INSERT = 
'INSERT OR IGNORE INTO `{TABLE}` (`user_id`, `item_id`, `pos`) VALUES (%i, %i, %i)';

const SQL_REMOVE = 
'UPDATE `{TABLE}` SET `pos` = 0 WHERE `user_id` = %i AND `item_id` = %i';

const SQL_MAXPOS =
'SELECT MAX(`pos`) as `pos` FROM `{TABLE}` WHERE `user_id` = %i';
    
    public function sort( $pos, array $item_ids, $ignore_dupes = FALSE ){
        $batch = array();
        $ct = 0;
        $local_txn = $this->claimStart();
        foreach( $item_ids as $item_id ){
            $pos = bcadd($pos, 1);
            $rs = $this->execute($this->sql('INSERT'), $this->user_id, $item_id, $pos );
            $ct += $curr = $rs->rowCount();
            if( ! $ignore_dupes && ! $curr  ){
                $rs = $this->execute($this->sql('UPDATE'), $pos, $this->user_id, $item_id );
                $ct += $rs->rowCount();
            }
        }
         if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        return $ct;
    }
    
    public function remove( $item_id ){
        $rs = $this->execute( $this->sql('REMOVE'), $this->user_id, $item_id );
    }
    
   /**
    * get the position for a list of item ids. triggered by the cache callback in FETCH.
    * @returns the positions, keyed by item id.
    */
    public function fetchPos( array $ids ){
        $rs = $this->db->execute($this->sql('SELECT'), $this->user_id, $ids );
        $list = array();
        while( $row = $rs->fetch(\PDO::FETCH_ASSOC) ){
            $list[ $row['item_id'] ] = $row['pos'];
        }
        $rs->closeCursor();
        return $list;
    }
    
   /**
    * what is the largest position number we have in our sort list?
    */
    public function maxPos(){
        $rs = $this->execute($this->sql('MAXPOS'), $this->user_id );
        $row = $rs->fetch(\PDO::FETCH_ASSOC);
        $rs->closeCursor();
        return $row['pos'];
    }
 
} // EOC


