<?php
namespace Gaia;
use Gaia\Exception;
use Gaia\DB\Transaction;

/**
 * @package GAIAONLINE
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/GAIAONLINE.txt
 */
class Souk implements Souk\Iface {
    
   /**
    * @string   the application id. must be lowercase alphanumeric (underscore okay)
    */
    protected $app;
    
    protected $user_id;
    
        
   /**
    * class constructor.
    * @param string                     app
    * @param int                        user_id, optional
    */
    public function __construct( $app, $user_id = NULL){
        // validate the app
        if( ! Souk\Util::validateApp( $app ) ) {
            throw new Exception('invalid app', $app);
        }
        
        // don't have to pass in a user id, but if you do, make sure it is an int.
        if( $user_id !== NULL && ! Souk\Util::validatePositiveInteger( $user_id ) ){
            throw new Exception('invalid user', $user_id);
        }
        
        // attach the user id
        $this->user_id = $user_id;
        
        // attach the app.
        $this->app = $app;
    }

   /**
    * returns the application string for this souk.
    */
    public function app(){
        return $this->app;
    }
    
   /**
    * returns the user_id for this souk. might be null, if no user_id was passed ot the constructor.
    */
    public function user(){
        return isset( $this->user_id ) ? $this->user_id : NULL;
    }
    
    
    /**
    * create a new auction listing in souk.
    * pass in an array or a listing object.
    * You can combine any of these parameters to create a listing:
    *    $listing = $souk->auction( array(
    *                                   'price'=>10, 
    *                                   'item_id'=>32, 
    *                                   'quantity'=>5, 
    *                                   'bid'=>100,
    *                                   'step'=>10,
    *                                   'price'=>1000,
    *                                   'expires'=>currentTime() + 86400) );
    *
    * @return   Souk\Listing
    */
    public function auction( $l, array $data = NULL ){
        // wrap in try catch so we can handle db transactions
        try {
            // create a transaction if we don't have one already.
            Transaction::start();
            
            // validate the listing
            $listing = Souk\Util::validateListing( $l );
            
            // if no seller passed in, use the current user id.
            if( ! isset( $listing->seller ) ) $listing->seller = $this->user();
            
            // make sure the seller is a positive int.
            if( ! Souk\Util::validatePositiveInteger( $listing->seller ) ) {
                throw new Exception('seller invalid', $listing->seller);
            }
            
            // write the listing into the db.
            $this->createListing( $listing );
            
            // commit transaction if it was created internally.
            Transaction::commit();
            
            // all done.
            return $listing;
            
        // something bad happened.
        } catch( Exception $e ){
            // roll back the transaction.
            Transaction::rollback();
            
            // wrap the exception in our own exception so we can control the message.
            $e = new Exception('cannot auction: ' . $e->getMessage(), $e->__toString() );
            
            // throw the exception up the chain.
            throw $e;
        }
    }
    
   /**
    * Close a listing ... 
    * Make the top bidder the buyer
    * or if no one has bid on it, return to the owner.
    * @return Souk\Listing
    */
    public function close( $id, array $data = NULL ){
        // create a transaction internally if one doesn't exist yet
        Transaction::start();
        try {
            // grab the listing within the transaction.
            $listing = $this->get( $id, $locked = TRUE);
            
            // can't close a non-existent listing.
            if( ! $listing || ! $listing->id ) {
                throw new Exception('not found', $id );
            }
            
            // if we are already closed, don't do anything.
            if( $listing->closed ){
                throw new Exception('already closed', $listing );
            }
            
            // set up the prior state of the listing before going further.
            $listing->setPriorState( $listing );
            
            // set the last touch to the current time
            $listing->touch = Souk\Util::now();
            
            // make the bidder the buyer if they won the bid.
            if( $listing->bidder && $listing->bid > $listing->reserve ){
                $listing->buyer = $listing->bidder;
            }
            
            // mark the listing as closed.
            $listing->closed = '1';
            
            $this->storage()->close( $listing );
            
            // if we created the transaction internally, commit it.
            Transaction::commit();
            
            // all done.
            return $listing;
            
        // looks like we hit a problem ...
        } catch( Exception $e ){
            // roll back any db transaction stuff
            Transaction::rollback();
            
            // toss the exception up the chain.
            throw $e;
        }
    }
    
   /**
    * buy a listing immediately ...
    * only works with those items that set a price for buy-now
    * @return Souk\Listing.
    */
    public function buy($id, array $data = NULL ){
        // create an internal transaction if one hasn't been passed in
        Transaction::start();
        try {
            // get a row lock on the listing.
            $listing = $this->get( $id, TRUE);
            
            // if we didn't get any listing, toss an exception.
            if( ! $listing || ! $listing->id ) {
                throw new Exception('not found', $id );
            }
            
            // need the current time to make sure it is a valid time to buy
            $ts = Souk\Util::now();
            
            // we assume the current user is the buyer
            $buyer = $this->user();
            
            // if no user id passed into the constructor, can't buy
            if( ! Souk\Util::validatePositiveInteger( $buyer ) ) {
                throw new Exception('invalid buyer', $buyer );
            }
            
            // can't buy if the listing is already closed
            if( $listing->closed ){
                throw new Exception('sold', $listing );
            }
            
            // can't buy if the seller is the same as the buyer.
            if( $listing->seller == $buyer ){
                throw new Exception('invalid buyer', $listing );
            }
            
            // can't buy if no price was set.
            if( $listing->price < 1 ){
                throw new Exception('bid only', $listing );
            }
            
            // cant buy if the listing has expired.
            if( $listing->expires <= $ts ){
                throw new Exception('expired', $listing );
            }
            
            // keep a pristine copy of what the listing looks like right now for later.
            // other wrapper layers can use this to make comparisons to what changed.
            $listing->setPriorState( $listing );
            
            $listing->touch = $ts;
            
            $listing->closed = 1;
            
            $listing->buyer = $buyer;
            
            $this->storage()->buy( $listing );
            
            // if we created the transaction internally, commit it.
            Transaction::commit();
            
            // done.
            return $listing;
            
        // looks like something went wrong along the way.
        } catch( Exception $e ){
            // revert any transaction stuff.
            // (detach an internal transaction if there is one)
            Transaction::rollback();
            
            // toss an exception.
            throw $e;
        }
    }
    
   /**
    * bid on an item
    * only works with those listings that set an opening bid (even if that amount is zero).
    * We use the proxy-bid system here, as used by ebay:
    * @see http://en.wikipedia.org/wiki/Proxy_bid
    * the winning bidder pays the price of the second-highest bid plus the step
    */
    public function bid( $id, $bid, array $data = NULL ){
        // normalize the data.
        $data = new Store\KVP( $data );
        
        // create an internal transaction if no transaction has been passed in.
        Transaction::start();
        try {
            // we assume the current user is always the bidder
            $bidder = $this->user();
            
            // if no bidder was passed into the constructor, blow up.
            if( ! Souk\Util::validatePositiveInteger( $bidder ) ) {
                throw new Exception('invalid bidder', $bidder );
            }
            
            // get a row lock on the listing.
            $listing = $this->get( $id, TRUE);
            if( ! $listing || ! $listing->id ) {
                throw new Exception('not found', $id );
            }
            
            // need the current time to do some comparisons.
            $ts = Souk\util::now();
            
            // don't go anywhere if the bidding is already closed.
            if( $listing->closed ){
                throw new Exception('closed', $listing );
            }
            
            // can't let the seller bid on the listing.
            if( $listing->seller == $bidder ){
                throw new Exception('invalid bidder', $listing );
            }
            
            // step is set when it is a biddable item. if it isn't there, don't allow bidding.
            if( $listing->step < 1 ){
                throw new Exception('buy only', $listing );
            }
            
            // has time expired on this listing? 
            if( $listing->expires <= $ts ){
                throw new Exception('expired', $listing );
            }
            
            // make sure we bid enough to challenge the current bid level.
            // if proxy bidding is enabled we still might not win the bid,
            // but at least we pushed it up a bit.
            if( $listing->bid + $listing->step > $bid ){
                throw new Exception('too low', $listing );
            }
            
            // keep a pristine copy of the listing internally so other wrapper classes can compare
            // afterward and see what changes were made.
            // The Souk\stockpile adapter especially needs this so it can return escrowed bids
            // to the previous bidder.
            $listing->setPriorState( $listing );
            
            // if proxy bidding is enabled, this gets a little more complicated.
            // proxy bidding is where you bid the max you are willing to pay, but only pay
            // one step above the previous bidder's level.
            // This is how ebay runs its auction site.
            // this means when you bid, we track your max amount you are willing to spend, but only
            // bid the minimum. When the next bid comes in, we automatically up your bid for you
            // until you go over your max amount and someone else takes the lead.
            // this approach makes the escrow system more efficient as well since it can excrow your
            // maximum amount all at once, and then refund when you get outbid or refund the difference 
            // if you get it for a lower bid.
            

    
            if( $data->enable_proxy ){
                // looks like the previous bidder got outbid.
                // track their maximum amount, and set the bid based on one step above the previous bid.
                if( $bid >= $listing->proxybid + $listing->step ){
                    $listing->bid = $listing->proxybid + $listing->step;
                    $listing->proxybid = $bid;
                    $listing->bidder = $bidder;
                    $listing->bidcount = $listing->bidcount + 1;
                
                //  the other bidder is still the winner of the bid. our bid didn't go over their
                // max bid amount. Bump up their bid amount to what we bid, and increment the 
                // bid count by 2, since we bid on it, and they bid back.
                } else {
                    $listing->bid = $bid;
                    $listing->bidcount = $listing->bidcount + 2;
                }
            
            // in this case, not a proxy bid system, just a straight up english auction.
            // don't worry about previous bidder. we know we bid more than the previous bidder,
            // so pump up the bid to whatever we passed in.
            } else {
                $listing->bid = $bid;
                $listing->bidder = $bidder;
                $listing->bidcount = $listing->bidcount + 1;
                
            }
            
            $listing->touch = $ts;
            
            $this->storage()->bid( $listing );
            
            Transaction::commit();
            
            // done.
            return $listing;
            
            
        // something went wrong ...
        } catch( Exception $e ){
            // revert the transaction ...
            // if it was created internally, remove it.
            Transaction::rollback();
            
            // toss the exception again.
            throw $e;
        }
    }
    
   /**
    * Grab a single listing by id.
    * if the lock parameter is passed in, we attach it to a transaction, and do a select lock ...
    * And throw an exception if no row is found.
    * otherwise, if no row is found, returns null.
    * @return Souk\Listing.
    */
    public function get( $id, $lock = FALSE ){
        $res = $this->fetch( array( $id ), $lock );
        if( ! isset( $res[ $id ] ) ) {
            if( $lock ) throw new Exception('not found');
            return NULL;
        }
        return $res[ $id ];
    }
    
   /**
    * Get multiple listings back, by id
    * @return array
    */
    public function fetch( array $ids, $lock = FALSE){
        return $this->storage()->fetch( $ids, $lock );
    }
    
   /**
    * Search for listings, sort and filter
    * pass in an array of options from the following:
    * sort, only, floor, ceiling, seller, buyer, bidder, item_id
    * @see https://intranet.gaiaonline.com/wiki/devs/souk#search_for_listings
    * @return array
    */
    public function search( $options ){
        // standardize the search options. makes it easer to manipulate.
        $options = Souk\Util::searchOptions( $options );
        
        return $this->storage()->search( $options );
    }
    
    /**
    * Find all the listings that are ready to be closed.
    * Age specifies how many seconds past expiration they are.
    * @return mysql result set object from the dao.
    */
    public function pending( $age = 0 ){
        return $this->storage()->pending( $age );
    }
    
    /************************       PROTECTED METHODS BELOW      **********************************/
    
    
    /**
    * create a new listing in the dao.
    * doesn't do any of the sanitization or validation, just
    * does the raw heavy lifting of assigning all the values to the dao and running the query.
    */
    protected function createListing( Souk\Listing $listing ){
        $this->storage()->createListing( $listing );
    }
    
    protected function storage(){
        return Souk\Storage::get( $this );
    }
}

// EOC
