<?php
namespace Gaia\Stockpile\Storage\MyPDO;
use \Gaia\Stockpile\Exception;


class Tally extends Core {

    const TABLE = 'tally';

    const SQL_CREATE = "CREATE TABLE IF NOT EXISTS `{TABLE}` (
                                  `user_id` bigint unsigned NOT NULL,
                                  `item_id` int unsigned NOT NULL,
                                  `quantity` bigint unsigned NOT NULL DEFAULT '0',
                                  UNIQUE KEY  (`user_id`,`item_id`)
                                ) ENGINE=InnoDB";
    
    const SQL_ADD = 
        'INSERT into `{TABLE}` (`user_id`, `item_id`, `quantity`) 
            VALUES (%i, %i, @STOCKPILE_TALLY:= `quantity` + %i)
            ON DUPLICATE KEY UPDATE `quantity` = @STOCKPILE_TALLY:=`quantity` + VALUES(`quantity`)';
    
    const SQL_SELECT_TOKEN = 'SELECT @STOCKPILE_TALLY as tally';
    
    const SQL_SUBTRACT = 
        'UPDATE `{TABLE}` SET `quantity` = @STOCKPILE_TALLY:=`quantity` - %i 
            WHERE user_id = %i AND `item_id` = %i AND `quantity` >= %i';
    
    const SQL_FETCH_ITEM =
        'SELECT `item_id`, `quantity` FROM `{TABLE}` WHERE user_id = %i AND item_id IN( %i )';
    
    const SQL_FETCH = 
        'SELECT `item_id`, `quantity` FROM `{TABLE}` WHERE user_id = %i';
    
    
    public function add( $item_id, $quantity ){
        $rs = $this->execute($this->sql('ADD'),$this->user_id, $item_id, $quantity );
        $rs = $this->execute($this->sql('SELECT_TOKEN'));
        $row = $rs->fetch(\PDO::FETCH_ASSOC);
        $rs->closeCursor();
        if( ! $row ) throw new Exception('database error', $this->db );
        return $row['tally'];
    }
    
    public function subtract( $item_id, $quantity ){
        $rs = $this->execute($this->sql('SUBTRACT'), $quantity, $this->user_id,$item_id,$quantity );
        if( $rs->rowCount() < 1 ) {
            throw new Exception('not enough left to subtract', $this->db );
        }
        $rs = $this->execute($this->sql('SELECT_TOKEN'));
        $row = $rs->fetch(\PDO::FETCH_ASSOC);
        $rs->closeCursor();
        if( ! $row ) throw new Exception('database error', $this->db );
        return $row['tally'];
    }
    
    public function fetch( array $item_ids = NULL, $with_lock = FALSE ){
        $lock = '';
        if( $with_lock ) $lock = ' FOR UPDATE';
        if( is_array( $item_ids ) ) {
            $rs = $this->execute( $this->sql('FETCH_ITEM') . $lock, $this->user_id, $item_ids );
        } else {
            $rs = $this->execute($this->sql('FETCH') . $lock, $this->user_id );
        }
        $list = array();
        while( $row = $rs->fetch(\PDO::FETCH_ASSOC) ){
            if( $row['quantity'] < 1 ) continue;
            $list[ $row['item_id'] ] = $row['quantity'];
        }
        $rs->closeCursor();
        return $list;
    }
    
} // EOC

