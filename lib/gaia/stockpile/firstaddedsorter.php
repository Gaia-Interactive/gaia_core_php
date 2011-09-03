<?php
namespace Gaia\Stockpile;
use \Gaia\DB\Transaction;

/**
 * This class is experimental and may be removed.
 * Intended to demonstrate how custom sorting functionality can be added to stockpile.
 * If you want to use it, email jloehrer@gaiaonline.com for details
 */
class FirstAddedSorter extends Sorter {
    
   /**
    * @see Stockpile_Interface::add();
    * only bump up to the top the first time we add this item id to the inventory.
    * after that, just let it slide.
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        $res = $this->core->add( $item_id, $quantity, $data );
        $now = Base::time();
        try {
            $ct = $this->storage('sorter')->sort( $now, array( $item_id ), $ignore = TRUE );
        } catch( \Exception $e ){
            throw $this->handle( $e );
        }
        if( $ct < 1 || ! $this->cacher) return $res;        
        $this->cacher->set($item_id, $now, 0, $this->cacheTimeout() );
        if( $this->inTran() ) Transaction::onRollback( array( $cache, 'delete'), array($item_id) );
        return $res;
    }
    
} // EOC


