<?php
namespace Gaia\Stockpile\Storage\SQLite;
use \Gaia\Stockpile\Exception;
use Gaia\DB\Transaction;

class Tally extends Core {

    const TABLE = 'tally';

    public function schema(){
        $table = $this->table();
        return  "CREATE TABLE IF NOT EXISTS `{$table}` ( " .
                "`user_id` INTEGER NOT NULL, " .
                "`item_id` INTEGER NOT NULL, " .
                "`quantity` INTEGER NOT NULL DEFAULT '0', " .
                "UNIQUE  (`user_id`,`item_id`))";
    }
    
    
    public function add( $item_id, $quantity ){
        $local_txn = $this->claimStart();
        $table = $this->table();
        $sql = "INSERT OR IGNORE INTO `{$table}` " . 
                    "(`user_id`, `item_id`, `quantity`) VALUES (%i, %i, %i)";
        
        $rs = $this->execute($sql,$this->user_id, $item_id, $quantity );
        if( ! $rs->affected() ){
            $sql = "UPDATE {$table} SET `quantity` = `quantity` + %i " . 
                    "WHERE `user_id` = %i AND `item_id` = %i";
            $rs = $this->execute($sql,$quantity, $this->user_id, $item_id );
            if( ! $rs->affected() ){
                if( $local_txn ) Transaction::rollback();
                throw new Exception('database error', $this->dbInfo() );
            }
        }
        $sql = "SELECT `quantity` FROM `{$table}`  WHERE `user_id` = %i AND `item_id` = %i";
        $rs = $this->execute($sql, $this->user_id, $item_id);
        $row = $rs->fetch();
        $rs->free();
        if( ! $row ) {
            if( $local_txn ) Transaction::rollback();
            throw new Exception('database error', $this->dbInfo() );
        }
        
        if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        
        return $row['quantity'];
    }
    
    public function subtract( $item_id, $quantity ){
        $local_txn = $this->claimStart();
        $table = $this->table();
        $sql =  "UPDATE `{$table}` SET `quantity` = `quantity` - %i " . 
                "WHERE user_id = %i AND `item_id` = %i AND `quantity` >= %i";
        $rs = $this->execute($sql, $quantity, $this->user_id,$item_id, $quantity );        
        if( $rs->affected() < 1 ) {
            if( $local_txn ) Transaction::rollback();
            throw new Exception('not enough', $this->dbInfo() );
        }
        $sql = "SELECT `quantity` FROM `{$table}`  WHERE `user_id` = %i AND `item_id` = %i";
        $rs = $this->execute($sql, $this->user_id, $item_id);
        $row = $rs->fetch();
        $rs->free();
        if( ! $row ) {
            if( $local_txn ) Transaction::rollback();
            throw new Exception('database error', $this->dbInfo() );
        }
        
        if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        
        return $row['quantity']; 
    }
    
    public function fetch( array $item_ids = NULL ){
        $table = $this->table();
        if( is_array( $item_ids ) ) {
            $sql = "SELECT `item_id`, `quantity` FROM `{$table}` WHERE `user_id` = %i AND `item_id` IN( %i )";
            $rs = $this->execute( $sql, $this->user_id, $item_ids );
        } else {
            $sql = "SELECT `item_id`, `quantity` FROM `{$table}` WHERE `user_id` = %i";
            $rs = $this->execute($sql, $this->user_id );
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

