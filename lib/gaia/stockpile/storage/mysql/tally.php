<?php
namespace Gaia\Stockpile\Storage\MySQL;
use \Gaia\Stockpile\Exception;


class Tally extends Core {

    const TABLE = 'tally';

    public function schema(){
        $table = $this->table();
        return "CREATE TABLE IF NOT EXISTS `{$table}` ( " .
                "`user_id` bigint unsigned NOT NULL, " .
                "`item_id` int unsigned NOT NULL, " .
                "`quantity` bigint unsigned NOT NULL DEFAULT '0', " .
                "UNIQUE KEY  (`user_id`,`item_id`) " .
                ") ENGINE=InnoDB";
    }
    
    public function add( $item_id, $quantity ){
        $table = $this->table();
        $sql =  "INSERT into `$table` (`user_id`, `item_id`, `quantity`) " . 
                'VALUES (%i, %i, @STOCKPILE_TALLY:= `quantity` + %i) ' .
                'ON DUPLICATE KEY UPDATE `quantity` = @STOCKPILE_TALLY:=`quantity` + VALUES(`quantity`)';
        $rs = $this->execute($sql,$this->user_id, $item_id, $quantity );
        $rs = $this->execute('SELECT @STOCKPILE_TALLY as tally');
        $row = $rs->fetch();
        $rs->free();
        if( ! $row ) throw new Exception('database error', $this->db );
        return $row['tally'];
    }
    
    public function subtract( $item_id, $quantity ){
        $table = $this->table();
        $sql =  "UPDATE `$table` SET `quantity` = @STOCKPILE_TALLY:=`quantity` - %i " . 
                "WHERE user_id = %i AND `item_id` = %i AND `quantity` >= %i";
        $rs = $this->execute($sql, $quantity, $this->user_id,$item_id,$quantity );
        if( $rs->affected() < 1 ) {
            throw new Exception('not enough left to subtract', $this->db );
        }
        $rs = $this->execute('SELECT @STOCKPILE_TALLY as tally');
        $row = $rs->fetch();
        $rs->free();
        if( ! $row ) throw new Exception('database error', $this->db );
        return $row['tally'];
    }
    
    public function fetch( array $item_ids = NULL, $with_lock = FALSE ){
        $lock = '';
        if( $with_lock ) $lock = ' FOR UPDATE';
        $table = $this->table();
        if( is_array( $item_ids ) ) {
            $sql = "SELECT `item_id`, `quantity` FROM `{$table}` WHERE user_id = %i AND item_id IN( %i )" . $lock;
            $rs = $this->execute( $sql, $this->user_id, $item_ids );
        } else {
            $sql = "SELECT `item_id`, `quantity` FROM `{$table}` WHERE user_id = %i";
            $rs = $this->execute($sql . $lock, $this->user_id );
        }
        $list = array();
        while( $row = $rs->fetch() ){
            if( $row['quantity'] < 1 ) continue;
            $list[ $row['item_id'] ] = $row['quantity'];
        }
        $rs->free();
        return $list;
    }
    
} // EOC

