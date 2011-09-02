<?php
namespace Gaia\Stockpile\Storage\MyPDO;
use \Gaia\Stockpile\Exception;

class Serial extends Core {
    
    const TABLE = 'serial';
    
    const SQL_CREATE =
"CREATE TABLE IF NOT EXISTS `{TABLE}` (
  `user_id` bigint unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `serial` bigint unsigned NOT NULL,
  `properties` varchar(5000) character set utf8,
  `soft_delete` tinyint UNSIGNED NOT NULL DEFAULT '0',
  UNIQUE KEY `user_id_item_id_serial` (`user_id`,`item_id`,`serial`),
   KEY `user_id_item_id` (`user_id`, `soft_delete`, `item_id`)
) ENGINE=InnoDB";

const SQL_ADD = 
'INSERT INTO `{TABLE}` (`user_id`, `serial`, `item_id`, `properties`, `soft_delete`) VALUES 
%s 
ON DUPLICATE KEY UPDATE `properties` = VALUES(`properties`), `soft_delete` = VALUES(`soft_delete`)';

const SQL_SUBTRACT = 
'UPDATE {TABLE} SET `soft_delete` = 1 WHERE 
`user_id` = %i AND 
item_id = %i AND 
`serial` IN (%i) AND 
`soft_delete` = 0';

const SQL_FETCH =
'SELECT `item_id`, `serial`, `properties` FROM `{TABLE}` 
 WHERE `user_id` = %i AND `soft_delete` = 0';
 
const SQL_FETCH_ITEM =
'SELECT `item_id`, `serial`, `properties` FROM `{TABLE}` 
 WHERE `user_id` = %i AND `soft_delete` = 0 AND `item_id` IN( %i )';
 
const SQL_VERIFY = 
'SELECT `serial` FROM `{TABLE}` 
WHERE `user_id` = %i AND `item_id` = %i AND `serial` IN ( %i )';


    public function add( $item_id, $quantity ){
        $batches = array();
        foreach( $quantity->all() as $serial => $properties ){
            $batches[] = $this->db->format_query('(%i, %i, %i, %s, 0)', $this->user_id, $serial, $item_id, json_encode( $properties ));
        }
        $rs = $this->execute( sprintf( $this->sql('ADD'), implode(",\n", $batches)) );
        return TRUE;
    }
    
    public function subtract( $item_id, $serials ){
        $rs = $this->execute( $this->sql('SUBTRACT'), $this->user_id, $item_id, $serials );
        return $rs->rowCount();
    }
    
    public function fetch( array $item_ids = NULL ){
        
        if( is_array( $item_ids ) ) {
            $rs = $this->execute( $this->sql('FETCH_ITEM'), $this->user_id, $item_ids );
        } else {
            $rs = $this->execute( $this->sql('FETCH'), $this->user_id );
        }
        $list = array();
        while( $row = $rs->fetch(\PDO::FETCH_ASSOC) ){
            if( ! isset( $list[ $row['item_id'] ] ) ) $list[ $row['item_id'] ] = array();
            $list[ $row['item_id'] ][ $row['serial'] ] = $this->deserializeProperties( $row['properties'] );
        }
        $rs->closeCursor();
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
        $rs = $this->execute( $this->sql('VERIFY'), $this->user_id, $item_id, $serials );
        $serials = array();
        while( $row = $rs->fetch(\PDO::FETCH_ASSOC) )  $serials[] = $row['serial'];
        $rs->closeCursor();
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

