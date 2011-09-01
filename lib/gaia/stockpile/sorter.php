<?php
namespace Gaia\Stockpile;
use \Gaia\Cache;
use \Gaia\DB\Transaction;

/**
 * base class for sorting.
 * Has a method that allows the app to pass in a sorted list of item ids, and those ids will
 * be added to the top of the list in the sort.
 * We can add other functions too if we find them useful.
 */
class Sorter extends Passthru {


    protected $cacher;
    
   /**
    * @see Stockpile_Passthru::__construct()
    * if we have a transaction object ...
    * set up a callback that will delete the index cache for this user if we rollback the txn.
    * this callback only occur once for each unique combination since the transaction onRollback
    * handler makes sure of this.
    */
    public function __construct( Iface $core, Cache\Iface $cacher = NULL ){
        parent::__construct( $core );
        if( ! $cacher ) return;
        $app = $this->app();
        $user_id = $this->user();
        $core_type = $this->coreType();
        $cacher = new Cache\Namespaced($cacher,  'stockpile/sort/' . $app . '/' . $user_id . '/');
        $this->cacher = $cacher;
    }
    
    
   /**
    * takes a simple list of item ids and pushes those items to the top of the list
    * starting with the first item id. doesn't need to be every item in the inventory.
    * just the ones you want sorted to the top of the list.
    * makes sense from an api standpoint but may not be perfect from an app standpoint.
    */
    public function sort( array $item_ids ){
        if( count( $item_ids ) < 1 ) return FALSE;
        rsort( $item_ids );
        $user_id = $this->user();
        $pos = $this->maxPos();
        $min = $this->minCustomPos();
        if( bccomp( $pos,  $min ) < 0 ) $pos = $min;
        if( $this->cacher ) {
            $timeout = $this->cacheTimeout();
            foreach( $item_ids as $item_id ){
                $pos = bcadd($pos, 1);
                $this->cacher->set( $item_id, $pos, 0, $timeout );
                if( $this->inTran() ) Transaction::onRollback( array( $this->cacher, 'delete'), array($item_id) );
            }
        }
        
        try {
            $this->storage('sorter')->sort( $pos, $item_ids );
        } catch ( Exception $e ){
            throw $this->handle( $e );
        }
        return TRUE;
    }

   /**
    * @see Stockpile_Interface::subtract();
    * do we need this function? if it turned into zero quantity, it already disappears.
    * do we need to clean up the sorting?
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        $res = $this->core->subtract( $item_id, $quantity, $data );
        if( Base::quantify( $res ) > 0 ) return $res;
        
        try {
            $this->storage('sorter')->remove( $item_id ); 
        } catch ( Exception $e ){
            throw $this->handle( $e );
        }
        if( $this->cacher ) $this->cacher->delete( $item_id );
        return $res;
    }
    
   /**
    * @see Stockpile_Interface::fetch();
    */
    public function fetch( array $item_ids = NULL ){
        $res = $this->core->fetch( $item_ids );
        $ids = array_keys( $res );
        if( count( $ids ) <= 1 ) return $res;
        if( $this->cacher ){
            $timeout = $this->cachetimeout();
            $options = array(
                'callback'=> array( $this, 'fetchPos'), // callback handler for missing rows.
                'method'=>'add', // use add method, so we don't accidentally clobber updates to the cache.
                'timeout' => $timeout, // how long do we want the data cached?
                'cache_missing'=> TRUE, // missing keys are stored in the cache, so we don't keep hitting the db.
            );
            $result = $this->cache()->get( $ids, $options );
        } else {
            $result = $this->fetchPos( $ids );
        }
        if( ! is_array( $result ) ) $result = array();
       
        $sorter = new CompareSort( $result );
        uksort( $res, array( $sorter, 'compare' ));
        
        return $res;
    }
    
   /**
    * get the position for a list of item ids. triggered by the cache callback in FETCH.
    * @returns the positions, keyed by item id.
    */
    public function fetchPos( array $ids ){
        try {
            return $this->storage('sorter')->fetchPos( $ids );
        } catch ( Exception $e ){
            throw $this->handle( $e );
        }
    }
    
   /**
    * what is the largest position number we have in our sort list?
    */
    protected function maxPos(){
        try {
            return $this->storage('sorter')->maxPos();
        } catch( Exception $e ){
            throw $this->handle( $e );
        }
    }
    
    /**
    * constant valiue of the unix timestamp for the year 2100. don't have to worry about reaching
    * it for 90 years. in the meantime, we can use the numbers above that value as reserved
    * for the custom position.
    * we could replace this value with 0 in this class and move this function to the date-specific
    * classes, but I'd rather leave an easy upgrade path.
    */
    protected function minCustomPos(){
        return bcadd(strtotime('01/01/2000'), 3600 * 24 * 365 * 100);
    }
    
   /**
    * get the cache timeout. if pointing at test data, use a shorter time.
    * tunable by config flag, per app? could add that easily.
    */
    protected function cacheTimeout(){
        return Cacher::cacheTimeout();
    }
 
} // EOC


