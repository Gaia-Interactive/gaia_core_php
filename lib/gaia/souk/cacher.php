<?php
namespace Gaia\Souk;
use Gaia\Store;
use Gaia\DB\Transaction;

/**
* This class is a wrapper class around Souk that allows us to cache the results in a write-thru
* approach. 
*/
class Cacher extends Passthru {
   
   /**
    * how long to cache the data
    */
    const CACHE_TIMEOUT = 86400;
    
    /**
    * cache searches differently because the cache becomes dirty quickly. 
    */
    const SEARCH_CACHE_TIMEOUT = 600;
    
    /**
    * cache prefix key
    */
    const SEARCH_VECTOR_PREFIX =  'sv01/';
    
    /**
    * refresh the cache, when reading?
    * @see self::forceRefresh
    */
    protected $refresh = FALSE;
    
    protected $cacher;
    
    public function __construct( Iface $core, Store\Iface $cacher ){
        parent::__construct( $core );
        $this->cacher = $cacher;
    }
    
    /**
    * perform the auction and store the results in the cache.
    */
    public function auction( $l, array $data = NULL ){
        return $this->writeToCache( $this->core->auction( $l, $data ) );
    }
    
    /**
    * close an auction and cache it.
    */
    public function close( $id, array $data = NULL ){
        return $this->writeToCache( $this->core->close( $id, $data ) );
    }
    
   /**
    * buy now, and cache results.
    */
    public function buy($id, array $data = NULL ){
        return $this->writeToCache( $this->core->buy( $id, $data ) );
    }
    
   /**
    * bid, and cache results.
    */
    public function bid( $id, $bid, array $data = NULL ){
        return $this->writeToCache( $this->core->bid( $id, $bid, $data ) );
    }
    
   /**
    * grab a list of auctions by id from the cache and repopulate missing records from the db.
    */
    public function fetch( array $ids, $lock = FALSE){
        if( $lock ) return $this->core()->fetch( $ids, $lock );
        $cache = $this->cache();
        $timeout = $this->cacheTimeout();
        if( count( $ids ) < 1 ) return array();
        if( $this->refresh ){
            $rows = $this->core->fetch( $ids, FALSE );
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
        return $result;
    }
    
   /**
    * cache searches by creating a checksum of the search parameters
    * to keep the cache more accurate we also keep a dynamic cache-bust key for certain parameters
    * so when listings affecting those parameters change the cache will be busted to reflect those changes.
    *
    * for example, let's say I was searching for all auctions from a given seller. When that seller
    * creates a new auction, a cache key will be updated for the seller. The value of that cache key are
    * always incorporated into my search checksum, so as a result the checksum changes and I instantly
    * see the new listing by that seller the moment the auction is created.
    *
    * only cache for a very short time if the search query is time sensitive.
    */
    public function search( $options ){
        $options = Util::SearchOptions( $options );
        $search_vector = $this->searchVectors( $options );
        $key = '-search-/' . md5( print_r( $options->export(), TRUE ) . $search_vector );
        $cache = $this->cache();
        $res = $cache->get( $key );
        if( is_array( $res ) ) return $res;
        $res = $this->core->search( $options );
        $timeout = $this->searchCacheTimeout();
        if( in_array( $options->sort , array( 'just_added', 'expires_soon', 'expires_soon_delay') ) ){
            $timeout = 30;
        }
        $cache->set($key, $res, $timeout );
        return $res;
    }
    
    /**
    * force the cache to be refreshed if needed.
    */
    public function forceRefresh( $v = TRUE ){
        $this->refresh = $v ? TRUE : FALSE;
    }
    
    /**
    * figure out how long to cache auction rows based on config settings.
    */
    public function cacheTimeout(){
        return self::CACHE_TIMEOUT;
    }
    
  /**
    * figure out how long to cache search queries based on config settings.
    */
    public function searchCacheTimeout(){
        return self::SEARCH_CACHE_TIMEOUT;
    }
    
   /**
    * write the record into the cache.
    */
    protected function writeToCache(Listing $listing ){
        $cache = $this->cache();
        $timeout = $this->cacheTimeout();
        $cache->set( $listing->id, $listing, 0, $timeout );
        if( ! Transaction::atStart() ){
            Transaction::onRollback( array( $cache, 'delete'), array($listing->id) );
        }
        $this->updateListingSearchVectors( $listing );
        return $listing;
    }
    
    /**
    * get the cacher object.
    */
    protected function cache($namespace = '' ){
        $app = $this->app();
        return new Store\Prefix($this->cacher,  'souk/' . $app . '/' . $namespace );
    }
    
    /**
    * generate a checksum based on the search query parameters.
    * we keep track of when a certain vector last changed in the cache, which will bust the checksum.
    * keeps the cache more honest.
    */
    protected function searchVectors( SearchOptions $options ){
        $keys = array();
        if( $options->seller ) $keys[] = 'seller' . $options->seller;
        if( $options->buyer ) $keys[] = 'buyer' . $options->buyer;
        if( $options->bidder ) $keys[] = 'bidder' . $options->bidder;
        if( count( $keys ) < 1 ) return '';
        $cache = $this->cache( self::SEARCH_VECTOR_PREFIX );
        $res = $cache->get( $keys );
        if( ! is_array( $res ) ) $res = array();
        foreach( $keys as $key ){
            if( ! isset( $res[ $key ] ) ){
                $res[ $key ] = $this->updateSearchVector( $key );
            }
        }
        return md5( print_r( $res, TRUE ) );
    }
    
   /**
    * when a listing changes, extract search vectors from that listing and update those cache keys
    * so that we can bust any cached search results that now may be invalid.
    */
    protected function updateListingSearchVectors( Listing $listing ){
        $this->updateSearchVector('seller' . $listing->seller);
        if( $listing->buyer ) $this->updateSearchVector('buyer' . $listing->buyer);
        if( $listing->bidder ) $this->updateSearchVector('bidder' . $listing->bidder);
        $prior = $listing->priorstate();
        if( $prior && $prior->bidder ) $this->updateSearchVector('bidder' . $prior->bidder);
    }
    
    /**
    * low level call to update a search vector cache key.
    */
    protected function updateSearchVector( $key ){
        $this->cache( self::SEARCH_VECTOR_PREFIX )->set( $key, Util::now() . '.' . mt_rand(1, 1000000000), 0 );
    }
}
// EOF
