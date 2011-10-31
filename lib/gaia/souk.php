<?php
namespace Gaia;
use Souk\Exception;
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
        // attach the transaction
        $this->tran = $tran;
        
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
            
            // grab the shard and row id.
            list( $shard, $row_id ) = Souk\Util::parseId( $listing->id );
            
            // update the listing.
            $table = 'souk_' . $app . '_' . $shard;
            $sql = "UPDATE $table SET buyer = %i, touch = %i, closed = 1, pricesort = NULL WHERE row_id = %i";
            $db = Transaction::instance('souk');
            $rs = $db->query($sql,
                        $listing->buyer,
                        $listing->touch,
                        $row_id);
            
            // if the query fails, toss an exception.
            if( ! $rs ) {
                throw new Exception('database error', $db );
            }
            
            // should have affected a row. if it didn't toss an exception.
            if( $rs->affected_rows < 1 ) {
                throw new Exception('failed', $rs );
            }
            
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
            
            // extract shard and row id from the id
            list( $shard, $row_id ) = Souk\Util::parseId( $id );
            
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
            
            // instantiate the listing dao and update the row with buyer information.
            $db = Transaction::instance('souk');
            
            $table = 'souk_' . $app . '_' . $shard;
            
            $sql = "UPDATE $table SET `buyer` = %i, `touch` = %i, `closed` = %i, `pricesort` = NULL WHERE `rowid` = %i";
            $rs = $db->query($sql, $listing->buyer = $buyer, $listing->touch = $ts,  $listing->closed = 1, $row_id);
            if( ! $rs ) throw new Exception('database error', $db );
            
            // should have affected 1 row. if it didn't something is wrong.
            if( $db->affected_rows < 1 ) throw new Exception('failed', $db );
            
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
            // this means when you bid, we track your max amount you are willing to spend, but only
            // bid the minimum. When the next bid comes in, we automatically up your bid for you
            // until you go over your max amount and someone else takes the lead.
            // this approach makes the escrow system more efficient as well since it can excrow your
            // maximum amount all at once, and then refund when you get outbid or refund the difference 
            // if you get it for a lower bid.
            if( $this->enableProxyBid() ){
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
            
            // extract shard and row id from the souk id.
            list( $shard, $row_id ) = Souk\Util::parseId( $id );
            
            // update the listing with the bidder's info. 
            $db = Connection::instance('souk');
            $db->begin();
            
            // create the table name
            $table = '';
            
            $sql = "UPDATE $table SET bid = %i, proxybid = %i, pricesort = %i, bidder = %i, touch = %i, bidcount = %i WHERE row_id = %i";
            $rs = $db->execute( $sql,
                    $listing->bid, 
                    $listing->proxybid, 
                    $this->calcPriceSort( $listing->bid, $listing->quantity ),
                    $listing->bidder,
                    $listing->touch = $ts,
                    $listing->bidcount,
                    $row_id
                    );
            
            // if the query failed, blow up.
            if( ! $rs ) {
                throw new Exception('database error', $db );
            }
            
            // should have affected 1 row. if not, blow up.
            if( $db->affected_rows < 1 ) {
                throw new Exception('failed', $rs );
            }
            
            // commit the transaction and remove it if it was created internally.
            $db->commit();
            
            // done.
            return $listing;
            
        // something went wrong ...
        } catch( Exception $e ){
            // revert the transaction ...
            // if it was created internally, remove it.
            $db->rollback();
            
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
        // stub out the result set so when we populate the data later, we will be 
        // returning the rows in the correct order as requested.
        // important when we do things like pass the list of ids from search into here,
        // where the ordering of ids matters.
        $result = array_fill_keys($ids, NULL);
        
        // we have to query by shard, which means we need to parse all the ids passed in
        // and group them by shard.
        $shardlist = array();
        foreach( $ids as $id ){
            list( $shard, $row_id ) = Souk\Util::parseId( $id );
            if( ! isset( $shardlist[ $shard ] ) ) $shardlist[ $shard ] = array();
            $shardlist[ $shard ][] = $row_id;
        }
        
        // need the transaction object in case we are in the middle of one.
        // this allows us to read data in a consistent transactional state.
        $tran = $this->transaction();
        
        // loop through the ids grouped by shard
        foreach( $shardlist as $shard => $row_ids ){
            // instantiate the dao and tie it to the transaction if need be.
            // query always by shard and row_id. this query is a primary key lookup 
            // so it should always be very efficient.
            $dao = Souk\Util::dao('listing');
            $dao->resolveApp( $this->app() );
            if( $tran ){
                if( $lock || $tran->inProgress() ) $tran->attach( $dao );
                if( $lock ) $dao->selectLock();
            }
            $dao->select('row_id, bid, proxybid, bidcount, item_id, quantity, price, step, created, expires, buyer, seller, bidder, reserve, closed, touch');
            $dao->setTableSuffix( $shard );
            $dao->byRowId( $row_ids );
            $rs = $dao->execute();
            
            // blow up if the query failed.
            if( ! $rs->isSuccess() ) {
                throw new Exception('database error', $rs );
            }
            
            // grab the rows returned and populate the result as Souk\listings.
            $row_ids = array();
            while(  $row = $rs->fetchrow(DB_ASSOC) ){
                $row_id = $row_ids[] = $row['row_id'];
                unset( $row['row_id'] );
                $listing = Souk\Util::listing( $row );
                $listing->id = Souk\Util::composeId( $shard, $row_id );
                $result[ $listing->id ] = $listing;
            }
            $rs->freeresult();
            
            // did we get any rows back? if not, move on to the next shard.
            if( count( $row_ids ) < 1 ) continue;
            
            // populate the listing with the serialized attributes that didn't map to any of
            // the predefined columns in souk. in most cases this table will be empty, but
            // we query it anyway to be sure.
            // don't need a row lock on this table because we have one on the main listing table.
            // since we always access these two tables in tandem, the prior row lock should 
            // serialize the requests and work for both.
            $dao = Souk\Util::dao('attributes');
            $dao->resolveApp( $this->app() );
            if( $tran && $tran->inProgress() ) $tran->attach( $dao );
            $dao->select('row_id, attributes');
            $dao->setTableSuffix( $shard );
            $dao->byRowId( $row_ids );
            $rs = $dao->execute();
            
            // blow up if we hit a query error.
            if( ! $rs->isSuccess() ) {
                throw new Exception('database error', $rs );
            }
            
            // no rows? no problem, just skip it. that is expected.
            if( $rs->affectedrows() < 1 ) continue;
            
            // someone stored attributes! merge them in.
            // stored as json in the db. deserialize and layer on top of the listing object.
            while( $row = $rs->fetchrow(DB_ASSOC) ){
                $id = Souk\Util::composeId( $shard, $row['row_id'] );
                if( ! isset( $result[ $id ] ) ) continue;
                $attributes = json_decode($row['attributes'], TRUE);
                if( !is_array( $attributes ) ) continue;
                $listing = $result[ $id ];
                foreach( $attributes as $k => $v ){
                    if( $k == 'id' ) continue;
                    $listing->$k = $v;
                }
            }
            // free the query result.
            $rs->freeresult();
        }
        
        // remember how we populated with nulls at the beginning? 
        // remove any that are still null, now that we are done.        
        foreach( array_keys( $result, NULL, TRUE) as $k) {
            unset( $result[ $k ] );
        }
        
        // return the result.
        return $result;
    }
    
   /**
    * Search for listings, sort and filter
    * pass in an array of options from the following:
    * sort, only, floor, ceiling, seller, buyer, bidder, item_id
    * @see https://intranet.gaiaonline.com/wiki/devs/souk#search_for_listings
    * @return array
    */
    public function search( $options ){
    /*
        // standardize the search options. makes it easer to manipulate.
        $options = Souk\Util::searchOptions( $options );
        
        // we query the listings table which is sharded by month.
        // because it is sharded by month, we have to do a few more tricks
        // to get the data-set we want.
        // first, we try to narrow it down as much as possible by the criteria specified.
        $dao = Souk\Util::dao('listing');
        $dao->resolveApp( $this->app() );
        $dao->select('row_id, pricesort, created, expires');
        
        // are we looking for auctions that are still in progress or already finished?
        $dao->byClosed($options->closed ? 1 : 0);
        
        // if we have a specific item id we are looking for, query for that.
        if( isset( $options->item_id ) ) $dao->andByItemId( $options->item_id );
        
        // do we know the seller?
        if( isset( $options->seller ) ) $dao->andBySeller( $options->seller );
        
        // sometimes, rarely we are looking for an auction purchased by a specific buyer.
        // obviously these auctions are already closed. should i sanity check the closed param?
        if( isset( $options->buyer ) ) $dao->andByBuyer( $options->buyer );
        
        // we can narrow by the person who is currently the leading bidder. can't search by past
        // bidders since that is more of a bid history search.
        // haven't written that yet.
        if( isset( $options->bidder ) ) $dao->andByBidder( $options->bidder );
        
        // are we looking for a bid-only auction?
        if( $options->only == 'bid' ) $dao->andByPrice(0);
        
        // how about a buy-now only auction?
        if( $options->only == 'buy' ) $dao->andByStep(0);
        
        // look for items only above a given price range.
        if( $options->floor && ctype_digit( $options->floor ) ) $dao->andComparePriceSort('>=', $options->floor );
        
        // look for items only below a given price range.
        if( $options->ceiling && ctype_digit( $options->ceiling )) $dao->andComparePriceSort('<=', $options->ceiling );
        
        // don't return more rows than the hard search limit imposed by souk.
        // after more than about 1000 rows, more results become meaningless. who paginates through all of that?
        // need them to somehow narrow their search more.
        $dao->limit(Souk\Util::SEARCH_LIMIT);
        
        // how do we want the result set sorted?
        $sort = $options->sort;
        switch( $sort ){
            case 'low_price':
                $dao->order('pricesort ASC');
                break;
            case 'high_price':
                $dao->order('pricesort DESC');
                break;

            case 'just_added':
                $dao->order('created DESC');
                break;
                
        
            case 'expires_soon':
                $dao->andCompareExpires('>', Souk\Util::now() );
                $dao->order('expires ASC');
                break;
                
            case 'expires_soon_delay':
                $dao->andCompareExpires('>', Souk\Util::now() + Souk\UTIL::MIN_EXPIRE );
                $dao->order('expires ASC');
                break;
                
            default:
                $key = $row['expires'] . '.' . $id;
                break;
        }
        
        // start with the shard a few weeks out from now, which is where the new listings are.
        $dao->affixTimestamp(Souk\Util::now() + Souk\UTIL::MAX_EXPIRE);
        
        // if the auction is still active, we don't have to search the really old shards.
        // stop at the shard for this month.
        if( ! $options->closed ) $dao->overrideDateCutoff(1);
        
        // start looping throw the shards and querying.
        $ids = array();
        foreach( Souk\Util::dateshard() as $shard ){
            // run the query
            $rs = $dao->execute();
            //print_r( $rs );
            //print "\n" . $rs->statement();
            
            // if the query fails, blow up.
            if( ! $rs->isSuccess() ) {
                throw new Exception('database error', $rs );
            }
            
            // extract the current shard from the dao.
            $shard = $dao->dateShard();
            
            // pull out all the rows matched by the query.
            // we are making the key of the id list contain the 
            // value of what we sort by, so we can do a keysort later, and order the
            // result in php since we have to span many shards.
            while( $row = $rs->fetchrow(DB_ASSOC)){
                $id = Souk\Util::composeId( $shard, $row['row_id'] );
                switch( $sort ){
                    case 'low_price':
                    case 'high_price':
                        $key = $row['pricesort'] . '.' . $id;
                        break;

                    case 'just_added':
                        $key = $row['created'] . '.' . $id;
                        break;
                        
                
                    case 'expires_soon':
                    case 'expires_soon_delay':
                    default:

                        $key = $row['expires'] . '.' . $id;
                        break;
                }
                $ids[ $key ] = $id;
                
            }
        } while( $dao->nextTable() );
        
        // now that we are all done fetching the rows, sort.
        switch( $sort ){
            case 'low_price':
            case 'expires_soon':
            case 'expires_soon_delay':
                ksort( $ids, SORT_NUMERIC );
                break;
                
            default:
                krsort( $ids, SORT_NUMERIC );
                break;
        }
        
        // since we queried many shards we could potentially have quite a few more
        // ids than the max limit. slice off only the top most rows.
        // those are the ones we care about.
        // we only need the other values so we can sort in php across all the shards.
        // a little bit inefficient, but it is just a list of numbers, and we are gonna
        // cache it for a long time in the caching layer.
        return array_values( array_slice( $ids, 0, Souk\Util::SEARCH_LIMIT) );
        */
    }
    
    /**
    * Find all the listings that are ready to be closed.
    * Age specifies how many seconds past expiration they are.
    * @return mysql result set object from the dao.
    */
    public function pending( $age = 0 ){
        $ts = Souk\Util::now() - $age;
        $stack = array();
        $db = Connection::get('souk');
        $app = $this->app();
        $list = array();
        foreach( Souk\Util::dateshard() as $shard ){
            $table = 'souk_' . $app . '_' . $shard;
            $rs = $db->execute("SELECT row_id FROM $table WHERE closed = 0 AND expires < ? ORDER BY expires ASC", $ts );
            if( ! $rs ) {
                throw new Exception('database error', $db );
            }
            while( $row = $rs->fetch_assoc() ) $list[] = Souk\Util::composeId( $shard, $row['row_id'] );
            
        }
        return $list;
    }
    
    /************************       PROTECTED METHODS BELOW      **********************************/
    
    
    /**
    * create a new listing in the dao.
    * doesn't do any of the sanitization or validation, just
    * does the raw heavy lifting of assigning all the values to the dao and running the query.
    */
    protected function createListing( Souk\Listing $listing ){

        // grab all the fields passed in that don't map to pre-defined fields.
        $attributes = array();
        foreach( array_diff( $listing->keys(), Souk\Util::fields() ) as $k ){
            if( $k == 'id' ) continue;
            $attributes[ $k ] = $listing->$k;
        }
        
        $shard = Souk\Util::dateshard()->shard();
        
        $app = $this->app();
        
        
        $table = 'souk_' . $app . '_' . $shard;
        
        $sql = "INSERT INTO $table
        (seller, created, expires, closed, buyer, bidder, bidcount, touch, price, pricesort, item_id, bid, step, reserve, quantity, attributes) 
        VALUES 
        (%i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %i, %s)";
        $rs = $db->execute($sql,
                $listing->seller,
                $listing->created,
                $listing->expires,
                $listing->closed,
                $listing->buyer,
                $listing->bidder,
                $listing->bidcount,
                $listing->touch,
                $listing->price,
                $this->calcPriceSort( $listing->price, $listing->quantity ),
                $listing->item_id,
                $listing->bid,
                $listing->step,
                $listing->reserve,
                $listing->quantity,
                json_encode($attributes)
                );
        if( ! $rs ) {
            throw new Exception('database error', $db );
        }
        $listing->id = Souk\Util::composeId( $shard,  $db->insert_id);
    }
    
   /**
    * we store a number in the db based on the price divided by the number of units, so that we 
    * can to s fair price-per-unit comparison of listings when sorting the result set.
    */
    protected function calcPriceSort( $price, $quantity ){
        return $price > 0 && $quantity > 0 ? ceil( $price / $quantity ) : 0;
    }
    
    /**
    * does this app allow proxy-bidding?
    * if so, souk figures out what the lowest bid you can make for you and still remain the leader.
    * This is how ebay runs its auction site.
    * gaia's marketplace isnt that smart.
    */
    public function enableProxyBid(){
        return TRUE; //config( $this->app() )->get( 'souk-proxybid' ) ? TRUE : FALSE;
    }
}

// EOC
