<?php
namespace Gaia\Stockpile\Storage\MyPDO;
use \Gaia\Stockpile\Exception;

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
  `user_id` bigint unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `pos` bigint unsigned NOT NULL default '0',
  UNIQUE KEY  (`user_id`,`item_id`),
  KEY `user_id_pos` ( `user_id`, `pos`)
) ENGINE=InnoDB";

const SQL_SELECT = 
    'SELECT `item_id`, `pos` FROM `{TABLE}` WHERE `user_id` = %i AND `item_id` IN ( %i )';

const SQL_INSERT =
'INSERT INTO `{TABLE}` (`user_id`, `item_id`, `pos`) VALUES 
 %s
ON DUPLICATE KEY UPDATE `pos` = VALUES(`pos`)';

const SQL_INSERT_IGNORE = 
'INSERT IGNORE INTO `{TABLE}` (`user_id`, `item_id`, `pos`) VALUES 
 %s';

const SQL_REMOVE = 
'UPDATE `{TABLE}` SET `pos` = 0 WHERE `user_id` = %i AND `item_id` = %i';

const SQL_MAXPOS =
'SELECT MAX(`pos`) as `pos` FROM `{TABLE}` WHERE `user_id` = %i';
    
    public function sort( $pos, array $item_ids, $ignore_dupes = FALSE ){
        $batch = array();
        foreach( $item_ids as $item_id ){
            $pos = bcadd($pos, 1);
            $batch[] = $this->db->format_query('(%i, %i, %i)', $this->user_id, $item_id, $pos );
        }
        $rs = $this->execute(sprintf( $this->sql( $ignore_dupes ? 'INSERT_IGNORE' : 'INSERT'), implode(",\n ", $batch )));
        return $rs->rowCount();
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


