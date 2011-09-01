<?php
namespace Gaia\Stockpile;
use \Gaia\Cache;
use \Gaia\DB\Transaction;


/**
 * A wrapper class for stockpile that caches the results.
 * This class implements a write-thru caching approach, breaking up the cached data into 2 parts.
 * The first part is the index and the second part is row-wise cache of the item_id => quantity
 * pairings.
 */
class Cacher extends Passthru {
   
   /**
    * cache data for a day. since we do a write-thu caching strategy, should never need refresh.
    */
    const CACHE_TIMEOUT = 86400;
    
   /**
    * Name of the index cache key.
    */
    const INDEX_CACHEKEY = '__index';
    
   /**
    * @bool     refresh the cache.
    */
    protected $refresh = FALSE;
    
    protected $cacher;
    
   /**
    * @see Stockpile_Passthru::__construct()
    * if we have a transaction object ...
    * set up a callback that will delete the index cache for this user if we rollback the txn.
    * this callback only occur once for each unique combination since the transaction onRollback
    * handler makes sure of this.
    */
    public function __construct( Iface $core, Cache\Iface $cacher ){
        parent::__construct( $core );
        
        $app = $this->app();
        $user_id = $this->user();
        $core_type = $this->coreType();
        $cacher = new Cache\Namespaced($cacher,  'stockpile/' . $app . '/' . $user_id . '/' . $core_type . '/');
        $this->cacher = $cacher;
        if( $this->inTran() ){
            Transaction::onRollback( array( $this->cacher, 'delete'), array(self::INDEX_CACHEKEY) );
        }
    }
    
   /**
    * @see Base::add()
    * run the core method call first and get back the total.
    * write the total into the cache for later.
    * return the total value back to the caller.
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        $total = $this->core->add( $item_id, $quantity, $data );
        $this->writeToCache( $item_id, $total );
        return $total;
    }
    
   /**
    * @see Base::subtract()
    * subtract the value in the database
    * write the value into the cache.
    * return to the caller.
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        $total = $this->core->subtract( $item_id, $quantity, $data );
        $this->writeToCache( $item_id, $total );
        return $total;
    }
      
   /**
    * @see StockPile_Interface::fetch()
    * works the same way, but gets its values from the cache when it can.
    * if no ids were passed in, that means they want the complete list.
    *  hopefully most of the time the calling app will specify which ids they are looking for.
    * much more efficient that way.
    * if no ids passed in and we get a cache miss, we gotta get all the keys again.
    *  might as well get the data too. when repopulating the row-wise cache, be careful not to
    * clobber data that was populated by add or subtract calls. only populate if missing.
    * use cache add approach ... much cleaner ... avoids race condition where
    * db updates at the same time some other process loads and accidentally
    * clobbers the value from the write.
    * if the refresh flag was set for this object, go ahead and overwrite anything in the cache.
    * this should only be used when debugging. not useful on production, and a little dangerous.
    * this method uses cache callback to do row-wise caching.
    * it uses the ids supplied as the keys, or if none supplied, the index that also lives in the cache.
    */
    public function fetch( array $ids = NULL ){
        $cache = $this->cacher;
        $timeout = $this->cacheTimeout();
        if( $ids === NULL ) {
            $ids = ! $this->refresh ? $cache->get(self::INDEX_CACHEKEY) : NULL;
            if( ! is_array( $ids ) ) {
                $rows = $this->core->fetch();
                $method = ! $this->refresh ? 'add' : 'set';
                $cache->$method(self::INDEX_CACHEKEY, array_keys( $rows ), $timeout );
                foreach( $rows as $k => $v ){
                    $cache->$method($k, $v, 0, $timeout );
                 }
                 return $rows;
            }        
        }
        if( count( $ids ) < 1 ) return array();
        if( $this->refresh ){
            $rows = $this->core->fetch( $ids );
            foreach( $rows as $k => $v ) $cache->set($k, $v, $timeout ); 
            return $rows;
        }
        $options = array(
            'callback'=> array( $this->core, 'fetch'), // callback handler for missing rows.
            'method'=>'add', // use add method, so we don't accidentally clobber updates to the cache.
            'timeout' => $timeout, // how long do we want the data cached?
            'cache_missing'=> TRUE, // missing keys are stored in the cache, so we don't keep hitting the db.
        );
        $result = $cache->get( $ids, $options );
        if( ! is_array( $result ) ) $result = array();
        foreach( $result as $item=>$quantity ) {
            $result[ $item ] = $this->defaultQuantity( $quantity );
        }
        return $result;
    }
    
    /**
     * The last touch is a combination of the current time along with a random value.
    * doesn't really need to be sequential, just reasonably unique.
    * we don't compare older/newer, just grab the cache key value and use it as a cache key prefix.
    * no way for us to ensure sequential time ids in a multi-server environment anyway.
    * could get into trouble if a process forked then called this method, because of how mt_rand seeds
    * itself.  Can fix that by adding pid to the key.
    */
    public function lastTouch( $refresh = FALSE ){
        $cacher = $this->cacher;
        $key = 'last_touch';
        $touch = ( ! $refresh && ! $this->refresh ) ? $cacher->get($key) : NULL;
        if( strlen( strval( $touch ) ) < 1 ){
            $touch = self::now() .'.' . mt_rand(0, 1000000) + self::posix_getpid();
            $cacher->set($key, $touch );
        }
        return $touch;
    }
    
    protected static function posix_getpid(){
        if( function_exists('posix_getpid') ) return posix_getpid();
        return 0;
    }
    
   /**
    * Utility method for forcing the cache to refresh. Can be used for debug purposes.
    */
    public function forceRefresh( $v = TRUE ){
        $this->refresh = $v ? TRUE : FALSE;
    }
    
    
    public static function now(){
        return Base::time();
    }
    
   /**
    * get the cache timeout. if pointing at test data, use a shorter time.
    * tunable by config flag, per app? could add that easily.
    */
    public function cacheTimeout(){
        return self::CACHE_TIMEOUT;
    }
    
   /**
    * Write the item_id => total quantity pairing into the cache.
    * @param int        item id
    * @param int        total count, after db write
    * if the total is less than zero, write it in as undefined in the cache so it doesn't hit the 
    * db again. With tally, the total is a number, but the serial total will be a quantity object.
    * no matter. we can get it's total value easily enough by just getting strval of it.
    * if there is a transaction attached, set up a callback to delete this key if the transaction doesn't work.
    * can't delete the cache key for history since variations are endless, but we can bust the key
    * using last touch. if we aren't in a transaction, just bust last touch right away.
    * check the id index so we can update it in the cache.
    * if nothing left of a given item, remove it from the index.
    * if the item id is in the index, no more work needed.
    * make sure the index is sorted the way it is supposed to be.
    * always sorted in numeric order of item id.
    */
    protected function writeToCache($item_id, $total ){
        $cache = $this->cacher;
        $timeout = $this->cacheTimeout();
        $cache->set( $item_id, Base::quantify( $total ) > 0 ? $total : Cache\Namespaced::UNDEF, $timeout );
        if( $this->inTran() ){
            Transaction::onRollback( array( $cache, 'delete'), array($item_id) );
            Transaction::onRollback( array( $this, 'lastTouch'), array(TRUE) );
            Transaction::onCommit( array( $this, 'lastTouch'), array(TRUE) );
        } else {
            $this->lastTouch( TRUE );
        }
        $index = $cache->get( self::INDEX_CACHEKEY );
        if( ! is_array( $index ) ) return;
        
        if( Base::quantify( $total ) > 0 ){
            if( in_array( $item_id, $index ) ) return;
            $index[] = $item_id;
            sort( $index, SORT_NUMERIC );
        } else {
            $found = array_keys( $index, $item_id );
            if( count( $found ) < 1 ) return;
            foreach( $found as $k ) unset( $index[ $k ] );
        }
        $cache->set( self::INDEX_CACHEKEY, $index, $timeout );
    }
} // EOC 


