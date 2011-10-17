<?php
namespace Gaia\Stockpile;
use \Gaia\DB\Transaction;


/**
 * Transfer an item from one account to another.
 * Takes a source and a destination account.
 * Use this to implement trades, marketplace, escrow, and other types of transfers.
 * could be your main account and your escrow account for marketplace,
 * or it could be you and another user ... or it could be your escrow account and another user.
 * In the marketplace example you will actually need 2 of these objects to transfer the items from 
 * three accounts. See the TapTest for marketplace for an example.
 */
class Transfer extends Passthru {
    
    protected $other;
    
   /**
    * pass in two accounts, always.
    */
    public function __construct( Iface $core, Iface  $other ){
        parent::__construct( $core );
        if( Transaction::atStart() ) {
            throw $this->handle( new Exception('need transaction to transfer') );
        }
        if( $core->app() == $other->app() && $core->user() == $other->user() ){
            throw $this->handle( new Exception('need two different parties to trade') );
        }
        if( $core->coreType() == $this->coreType() ){
            throw $this->handle( new Exception('core must not be a transfer') );
        }
        if( $other->coreType() == $this->coreType() ){
            throw $this->handle( new Exception('other must not be a transfer') );
        }
        if( $core->coreType() != $other->coreType() && 
            $core->coreType() != 'serial-tally' && 
            $other->coreType() != 'serial-tally' ){
            throw $this->handle( new Exception('both must be of same coretype in a transfer') );
        }
        $this->other = $other;
    }
    
   /**
    * transfer a quantity from someone else to me.
    * SELF   <-    OTHER
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        if( ! is_array( $data ) ) $data = array(); 
        
        $data['from_id'] = $this->core->user();
        $data['from_app'] = $this->core->app();
        
        
        if( $this->other->coreType() == 'serial' && ! $quantity instanceof Quantity ){
            $quantity = $this->other->get( $item_id )->grab( $quantity );
        }
        
        $this->other->subtract( $item_id, $quantity, $data );
        
        $data['from_id'] = $this->other->user();
        $data['from_app'] = $this->other->app();
        return $this->core->add( $item_id, $quantity, $data );
    }
    
   /**
    * transfer a quantity from me to someone else.
    * SELF   ->   OTHER
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        if( ! is_array( $data ) ) $data = array();
        
        if( $this->core->coreType() == 'serial' && ! $quantity instanceof Quantity ){
            $quantity = $this->core->get( $item_id )->grab( $quantity );
        }

        $data['from_id'] = $this->core->user();
        $data['from_app'] = $this->core->app();
        $this->other->add( $item_id, $quantity, $data );
        
        $data['from_id'] = $this->other->user();
        $data['from_app'] = $this->other->app();
        return $this->core->subtract( $item_id, $quantity, $data );
    }
    
   /**
    * core type
    */
    public function coreType(){
        return 'transfer';
    }

} // EOC
