<?php
namespace Gaia\Stockpile\Storage\LitePDO;
use \Gaia\Stockpile\Exception;
use Gaia\DB\Transaction;

class Tally extends Core {

    const TABLE = 'tally';

const SQL_CREATE = 
"CREATE TABLE IF NOT EXISTS `{TABLE}` (
  `user_id` INTEGER NOT NULL,
  `item_id` INTEGER NOT NULL,
  `quantity` INTEGER NOT NULL DEFAULT '0',
  UNIQUE  (`user_id`,`item_id`)
)";

const SQL_INDEX = "";

const SQL_ADD = 
"INSERT OR IGNORE INTO `{TABLE}` (`user_id`, `item_id`, `quantity`) VALUES (%i, %i, %i)";

CONST SQL_UPDATE =
"UPDATE {TABLE} SET `quantity` = `quantity` + %i WHERE `user_id` = %i AND `item_id` = %i";
    
const SQL_SELECT = 
'SELECT `quantity` FROM `{TABLE}`  WHERE `user_id` = %i AND `item_id` = %i';

const SQL_SUBTRACT = 
'UPDATE `{TABLE}` SET `quantity` = `quantity` - %i WHERE user_id = %i AND `item_id` = %i AND `quantity` >= %i';

const SQL_FETCH_ITEM =
'SELECT `item_id`, `quantity` FROM `{TABLE}` WHERE user_id = %i AND item_id IN( %i )';

const SQL_FETCH = 
'SELECT `item_id`, `quantity` FROM `{TABLE}` WHERE user_id = %i';
    
    public function add( $item_id, $quantity ){
        //$local_txn = $this->claimStart();
        $rs = $this->execute($this->sql('ADD'),$this->user_id, $item_id, $quantity );
        if( ! $rs->rowCount() ){
            $rs = $this->execute($this->sql('UPDATE'),$quantity, $this->user_id, $item_id );
            if( ! $rs->rowCount() ){
                //if( $local_txn ) Transaction::rollback();
                throw new Exception('database error', $this->dbInfo() );
            }
        }
        $rs = $this->execute($this->sql('SELECT'), $this->user_id, $item_id);
        $row = $rs->fetch(\PDO::FETCH_ASSOC);
        $rs->closeCursor();
        if( ! $row ) {
            //if( $local_txn ) Transaction::rollback();
            throw new Exception('database error', $this->dbInfo() );
        }
        /*
        if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        */
        return $row['quantity'];
    }
    
    public function subtract( $item_id, $quantity ){
        //$local_txn = $this->claimStart();
        $rs = $this->execute($this->sql('SUBTRACT'), $quantity, $this->user_id,$item_id, $quantity );        
        if( $rs->rowCount() < 1 ) {
            //if( $local_txn ) Transaction::rollback();
            throw new Exception('not enough', $this->dbInfo() );
        }
        $rs = $this->execute($this->sql('SELECT'), $this->user_id, $item_id);
        $row = $rs->fetch(\PDO::FETCH_ASSOC);
        $rs->closeCursor();
        if( ! $row ) {
            //if( $local_txn ) Transaction::rollback();
            throw new Exception('database error', $this->dbInfo() );
        }
        /*
        if( $local_txn ) {
            if( ! Transaction::commit()) throw new Exception('database error', $this->dbInfo() );
        }
        */
        return $row['quantity']; 
    }
    
    public function fetch( array $item_ids = NULL ){
        if( is_array( $item_ids ) ) {
            $rs = $this->execute( $this->sql('FETCH_ITEM'), $this->user_id, $item_ids );
        } else {
            $rs = $this->execute($this->sql('FETCH'), $this->user_id );
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

