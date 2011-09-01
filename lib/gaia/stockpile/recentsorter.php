<?php
namespace Gaia\Stockpile;
use \Gaia\DB\Transaction;

/**
 * Every time an item is modified in inventory, move it to the top of the sort list.
 * Simple timestamp based sorting.
 */
class RecentSorter extends Sorter {
    
   /**
    * @see Stockpile_Interface::add();
    * on insert, set the pos column of the sort table to match current time.
    * on dupe key violation, update the pos column to match current time
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        $res = $this->core->add( $item_id, $quantity, $data );
        try {
            $this->storage('sorter')->sort( Base::time(), array( $item_id ) );
        } catch ( Exception $e ){
            throw $this->handle( $e );
        }
        if( $this->cacher) {
            $this->cacher->set($item_id, $now, $this->cacheTimeout() );
            if( $tran ) Transaction::onRollback( array( $this->cacher, 'delete'), array($item_id) );
        }
        return $res;
    }
    
} // EOC


