<?php
namespace Gaia\Souk;
use Gaia\Stockpile;

/**
 * Stockpile adapter for souk.
 */
class Stockpile extends Passthru {
    
   /**
    *@StockpileBinder_Interface
    */
    protected $binder;
    
   /**
    * attach the core object and the binder, which connects stockpile data to souk.
    * This binder object should remain consistent throughout your application.
    */
    public function __construct( Stockpile\Iface $core, StockpileBinder_Iface $binder ){
        parent::__construct( $core );
        $this->binder = $binder;
    }
   
   /**
    * @see Souk::auction
    * take the item out of the seller's account and place it in escrow when creating the auction.
    * set up the quantity, which can be an integer or some other quantity from stockpile.
    */
    function auction( $l, array $data = NULL ){
        // wrap in a try/catch block so we can handle the transaction correctly, and rollback
        // if need be.
        try {
            // kick off a transaction. if there isn't one attached, create one.
            $this->start();
            
            // convert the data coming in, probably an array,into a Listing.
            $listing = Util::listing( $l );
            
            // if no seller passed in, use the current user.
            if( ! isset( $listing->seller ) ) $listing->seller = $this->user();
            
            // if no quantity specified, default to 1.
            if( ! isset( $listing->quantity ) ) $listing->quantity = 1;
            
            // connect to the seller's item account in stockpile
            $seller = $this->itemAccount( $listing->seller );
            
            // also need their escrow account
            $escrow = $this->itemEscrow( $seller );
            
            // bind them together in a stockpile transfer object so we can move items from 
            // their inventory into escrow. Prevents the seller from trading away the items while
            // simultaneously auctioning them off.
            $transfer = $this->transfer( $seller, $escrow );
            
            // if the inventory type is serial, then we need a stockpile quantity object.
            // if an integer or some other value was passed in for quantity, convert it into
            // the appropriate stockpile_quantity so we are sure we are vending the proper
            // serials.
            if( $seller->coreType() == 'serial' && ! $listing->quantity instanceof Stockpile_Quantity ){
                $listing->quantity = $seller->get( $listing->item_id )->grab( $listing->quantity );
            }
            
            // subtract the quantity from the inventory ... adds it transparently to escrow.
            $transfer->subtract( $listing->item_id, $listing->quantity, $this->prepData( $data, $listing, 'auction') );
            
            // now, we need to prep the quantity so that it can be stored in the listing in 
            // the database. we assign an export of the object to the stockpile_quantity attribute of the listing
            // that way when we deserialize it back out, we can reconstruct the object.
            if( $listing->quantity instanceof Stockpile_Quantity ) {
                $listing->stockpile_quantity = $listing->quantity->export();
            }
            
            // turn the quantity into a scalar representation so souk can write the quantity into the db row.
            $listing->quantity = $seller->quantify( $listing->quantity );
            
            // make sure the listing we return is deserialized correctly
            $listing = $this->prepListing( $this->core->auction( $listing ) );
            
            // commit the transaction, if we created it internally.
            $this->complete();
            
            // all done.
            return  $listing;
        } catch( Exception $e ){
            // ewww, something nasty happened. revert the transaction.
            $this->rollback();
            
            // toss the exception again up the chain.
            throw $e;
        }
    }
    
   /**
    * bid on an item.
    * @place the bid amount into escrow.
    * Since this is a proxy bid system, we check to see if our bid beats the current leader,
    * and if so, we take their bid max plus the bid increment as our new bid ... with our bid max.
    */
    function bid( $id, $bid, array $data = NULL ){
        // wrapped in try/catch so we can manage transactions.
        try {
            // kick off a transaction if we aren't attached to one already.
            $this->start();
            
            // send the bid off to the core object for the first step of the process.
            $listing = $this->prepListing( $this->core->bid( $id, $bid, $data ) );
            
            // grab the listing's state prior to the bid we just made.
            $prior = $listing->priorstate();
            
            // if there was a previous bid, take their bid out of escrow and refund it.
            if( $prior  && ( ! $this->enableProxyBid() || $prior->proxybid != $listing->proxybid ) ){
                $this->cancelBid( $listing->priorstate(), $data );
            }

            // if the bid actually changed hands, go ahead and escrow funds for the bidder.
             // so that the bidder can actually pay for what they bid when the time comes.
            if( ! $this->enableProxyBid() || ! $prior || $prior->proxybid != $listing->proxybid ){
            
                // set up a transfer object between the currency account and the currency escrow.
                $bidder = $this->transfer(   $this->currencyAccount($listing->bidder ), 
                                                    $this->currencyEscrow( $listing->bidder ) );
                
                // subtracting moves funds into escrow.
                $bidder->subtract( $this->currencyId(), $bid, $this->prepData( $data, $listing, 'bid') );
            }
            // commit the transaction if we started it.
            $this->complete();
            
            // all done. 
            return $listing;
            
        } catch( Exception $e ){
            // evil! revert the transaction.
            $this->rollback();
            
            // toss the exception again up the chain.
            throw $e;
        }
    }
    
   /**
    * @see Souk::buy()
    * buy immediately. no escrow needed for currency. Take directly from buyer and transfer currency to seller.
    * Then transfer items from seller escrow into buyer's item account.
    * Also, cancel any open bids that may be out there.
    */
    function buy($id, array $data = NULL ){
        // wrap in try/ catch so we can manage the transaction
        try {
            // kick off a transaction if none is attached yet.
            $this->start();
            
            // run the core logic first. buy the listing. if it fails, we are the first to know.
            $listing = $this->prepListing( $this->core->buy( $id, $data ) );
            
            $prior = $listing->priorState();
            
            // refund any funds that were escrowed in a previous bid.
            // if this is a buy-only listing, this will do nothing.
            if( $prior  && ( ! $this->enableProxyBid() || $prior->proxybid != $listing->proxybid ) ){
                $this->cancelBid( $listing->priorstate(), $data );
            }
            // now that we were able to claim the listing, lets pay for it right away.
            // no need for any escrow in this step. The funds go straight from the buyer to the seller.
            $buyer = $this->transfer( $this->currencyAccount( $listing->buyer ), $this->currencyAccount( $listing->seller ) );
            $buyer->subtract( $this->currencyId(), $listing->price, $this->prepData( $data, $listing, 'pay_seller') );
            
            // now that we've paid for it, let's transfer the item from escrow into the buyer's inventory.
            $buyer = $this->transfer(   $this->itemAccount( $listing->buyer ), 
                                        $this->itemEscrow( $listing->seller ) );
                                        
            // transferring from item escrow into the buyer's inventory.
            $v = $buyer->add( $listing->item_id, $listing->quantity, $this->prepData($data, $listing, 'buy') );
            
            // commit the transaction if we created it.
            $this->complete();
            
            // all done!
            return $listing;
        } catch( Exception $e ){
            // epic fail. revert the transaction
            $this->rollback();
            
            // toss the exception again.
            throw $e;
        }
    }
    
   /**
    * @see Souk::close()
    * close the bid and transfer currency from escrow into seller, and items to buyer.
    */
    function close( $id, array $data = NULL ){
        // wrap in try catch so we can manage transactions.
        try {
            // kick off a transaction if not attached already.
            $this->start();
            
            // do the core logic of closing the listing.
            $listing = $this->prepListing( $this->core->close( $id ) );
            
            // did someone successfully buy this listing?
            if( $listing->buyer ){
                // settle up!
                // start by transferring funds from the buyer's escrow to the seller's currency account.
                $buyer = $this->transfer( $this->currencyEscrow( $listing->buyer ), $this->currencyAccount( $listing->seller ) );
                
                // subtract moves money from escrow into seller's currency.
                $buyer->subtract( $this->currencyId(), $listing->bid, $this->prepData( $data, $listing, 'pay_seller') );
                
                // set up a transfer between the buyer's item account and the seller's escrow
                $buyer = $this->transfer( $this->itemAccount( $listing->buyer ), $this->itemEscrow( $listing->seller ) );
                
                // now, move the item from escrow into the buyer's item account.
                $buyer->add( $listing->item_id, $listing->quantity, $this->prepData($data, $listing, 'winbid') );
                
                // the buyer only pays the bid amount, not the max they were willing to pay,
                // since this is a proxy bid system.
                // that means if we escrowed extra money, we return it now.
                if( $listing->proxybid > $listing->bid ){
                    // figure out how much extra was escrowed.
                    $diff = $listing->proxybid - $listing->bid;
                    
                    // set up a transfer between currency escrow and the buyer's currency account.
                    $buyer = $this->transfer( $this->currencyAccount( $listing->buyer ), $this->currencyEscrow( $listing->buyer ) );
                    
                    // return the funds.
                    $buyer->add( $this->currencyId(), $diff );
                }
                
            // no one won the bid? WTF? Return the item to the owner.
            } else {
                // set up a transfer between the seller and their escrow account.
                $seller = $this->transfer( $this->itemAccount( $listing->seller ), $this->itemEscrow( $listing->seller ) );
                
                // return the item from the listing.
                $seller->add( $listing->item_id, $listing->quantity, $this->prepData($data, $listing, 'no_sale') );
                
                // if anyone bid on the listing, return their escrowed bid ... this happens if the reserve isn't met.
                $this->cancelBid( $listing, $data );
            }
            // commit the transaction if we started one internally.
            $this->complete();
            
            // all done.
            return $listing;
        } catch( Exception $e ){
            // what happened? roll back the transaction
            $this->rollback();
            
            // exception, get your freak on! Fly! be free!
            throw $e;
        }
    }
    
   /***
    * Convert any stockpile quantity items that were stored as an attribute under stockpile_quantity,
    * and put it in as a quantity.
    */
    public function fetch( array $ids, $lock = FALSE ){
        // grab the listing from the core.
        $res = $this->core->fetch( $ids, $lock );
        
        // before returning, translate the quantity object into a stockpile_quantity if needed.
        foreach( $res as $listing ) $this->prepListing( $listing );
        
        // all done.
        return $res;
    }
    
   /**
    * simple stockpile transfer factory method.
    */
    protected function transfer( $a, $b ){
        return new Stockpile\Transfer( $a, $b );
    }
    
   /**
    * utility method for logging. attaches meta-data from souk to the logging data payload.
    */
    protected function prepData( $data, Listing $listing, $action ){
        if( ! is_array( $data ) ) $data = array();
            $data['app'] = $this->app();
            $data['id'] = $listing->id;
            $data['action'] = $action;
            return $data;
    }
    
   /**
    * return any escrowed funds that were bid.
    * happens when you are outbid or when the listing is closed but the reserve isn't met.
    */
    protected function cancelBid( $listing, array $data = NULL ){
        // sanity check. make sure we got the object we expect.
        if( ! $listing instanceof Listing ) return;
        
        // can't do anything if this doesn't have a valid id.
        if( ! $listing->id ) {
            throw new Exception('not found', $listing );
        }
        
        // no bidder? no reason to refund.
        if( ! $listing->bidder ) return;
        
        // set up the transfer.
        $bidder = $this->transfer( $this->currencyAccount( $listing->bidder ), $this->currencyEscrow( $listing->bidder ) );
        
        // move the currency from escrow, back ot the bidder's currency account.
        $bidder->add( $this->currencyId(), $this->enableProxyBid() ? $listing->proxybid : $listing->bid, $this->prepData($data, $listing, 'bid_cancel') );
    }
    
   /**
    * factory method for instantiating the user's stockpile inventory.
    */
    protected function itemAccount( $user_id ){
        $stockpile = $this->binder->itemAccount( $user_id, $this->transaction() );
        if( ! $stockpile instanceof Stockpile_Interface ) {
            throw new Exception('invalid stockpile object', $stockpile );
        }
        return $stockpile;
    }
    
        
   /**
    * factory method for instantiating the user's stockpile currency account.
    */
    protected function currencyAccount( $user_id ){
        $stockpile = $this->binder->currencyAccount( $user_id, $this->transaction() );
        if( ! $stockpile instanceof Stockpile_Interface ) {
            throw new Exception('invalid stockpile object', $stockpile );
        }
        return $stockpile;
    }
    
        
   /**
    * what is the item id of the currency, in stockpile?
    */
    protected function currencyId(){
        $id = $this->binder->currencyId();
        if( ! Util::validatePositiveInteger( $id ) ) {
            throw new Exception('invalid currency id', $id );
        }
        return $id;
    }
    
   /**
    * if the binder doesn't specify, create a default stockpile escrow account for items escrowed
    * while being auctioned. otherwise, use the escrow object specified by the binder.
    */
    protected function itemEscrow( $stockpile ){
        if( method_exists( $this->binder, 'itemEscrow') ){
            $user_id = ( $stockpile instanceof Stockpile\Iface ) ? $stockpile->user() : $stockpile;
            $stockpile = $this->binder->currencyEscrow( $user_id, $this->transaction() );
            if( ! $stockpile instanceof Stockpile\Iface ) {
                throw new Exception('invalid stockpile object', $stockpile );
            }
            return $stockpile;
        }
        if( ! $stockpile instanceof Stockpile\Iface ) $stockpile = $this->itemAccount( $stockpile );
        
        switch( $stockpile->coreType() ) {
            case 'serial': 
                    $class = 'stockpile_serial';
                    break;
            
            case 'tally':
                    $class = 'stockpile_tally';
                    break;
            
            case 'serial-tally':
                    $class = 'stockpile_hybrid';
                    break;
            
            default:
                throw new Exception('invalid stockpile object', $stockpile );
        }
        
        return new $class( $stockpile->app() . '_souk', $stockpile->user());
    }
    
   /**
    * if the binder doesn't specify, create a default stockpile escrow account for currency escrowed
    * during the bidding process. otherwise, use the escrow object specified by the binder.
    */
    protected function currencyEscrow( $stockpile ){
        if( method_exists( $this->binder, 'currencyEscrow') ){
            $user_id = ( $stockpile instanceof Stockpile\Iface ) ? $stockpile->user() : $stockpile;
            $stockpile = $this->binder->currencyEscrow( $user_id, $this->transaction() );
            if( ! $stockpile instanceof Stockpile\Iface ) {
                throw new Exception('invalid stockpile object', $stockpile );
            }
            return $stockpile;
        }
        if( ! $stockpile instanceof Stockpile\Iface ) $stockpile = $this->currencyAccount( $stockpile );
        
        switch( $stockpile->coreType() ) {
            
            case 'tally':
                    $class = 'stockpile_tally';
                    break;
            
            case 'serial-tally':
                    $class = 'stockpile_hybrid';
                    break;
            
            default:
                throw new Exception('invalid stockpile object', $stockpile );
        }
        
        return new $class( $stockpile->app() . '_souk', $stockpile->user());
    }
    
   /**
    * translate the simple array of data we store in the db in souk back into a Stockpile_Quantity object.
    */
    protected function prepListing( Listing $listing ){
        $prior = $listing->priorstate();
        if( $prior ) $this->prepListing( $prior );
        if( ! isset( $listing->stockpile_quantity ) ) return $listing;
        $listing->quantity = $this->itemAccount( $listing->seller )->defaultQuantity( $listing->stockpile_quantity );
        unset( $listing->stockpile_quantity );
        return $listing;
    }
}
