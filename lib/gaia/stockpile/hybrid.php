<?php
namespace Gaia\Stockpile;
use Gaia\DB\Transaction;

/**
 * Basic Wrapper class that allows us to easily punch out parts of the class.
 * this makes it possible to add an decorate behavior of a core object.
 * YAY DESIGN PATTERNS!
 * This pattern always confuses the shit out of new devs.
 */
class Hybrid extends Passthru {
   
       
   /**
    * @Stockpile_Serial
    */
    protected $serial;
    
   /**
    * @see Stockpile_Core::__construct();
    */
    public function __construct( $app, $user_id){
        parent::__construct( new Tally( $app, $user_id ) );
        $this->serial = new Serial( $app, $user_id );
    }
    
   /**
    * @see Stockpile_Interface::add();
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        $local_txn = Transaction::claimStart() ? TRUE : FALSE;
        try {
            $quantity = $this->quantity( $quantity );
            if( $quantity->value() < 1 ){
                throw $this->handle( new Exception('cannot add: invalid quantity', $quantity ) );
            }
            if( $quantity->tally() > 0 ){
                $tally = $this->core->add( $item_id, $quantity->tally(), $data );
            } else {
                $tally = $this->core->get($item_id, $with_lock = TRUE);
            }
            if( count( $quantity->all() ) > 0 ){
                $serial = $this->serial->add( $item_id, $quantity->all(), $data );
            } else { 
                $serial = $this->serial->get( $item_id );
            }
            if( $local_txn ) {
                Transaction::commit();
            }
            return $this->quantity( array('tally'=>$tally, 'serial'=>$serial ) );        
    
        } catch( \Exception $e ){
            if( $local_txn) {
                if( Transaction::inProgress() ) Transaction::rollback();
            }
            $e = new Exception('cannot add: ' . $e->getMessage(), $e->__toString() );
            throw $e;
        } 
    }
    
   /**
    * Convert serial quantities into tally quantities, and tally quantities into serials.
    * If you pass in an integer, it will allocate that many of your existing tally count into serials,
    * deleting from tally and adding serials.
    * if you pass in a list of serials, it will delete those serials from the inventory and convert them
    * into tally.
    */
    public function convert( $item_id, $quantity, $data = NULL){
        $local_txn = Transaction::claimStart() ? TRUE : FALSE;
        try {
            if( Base::validatePositiveInteger( $quantity ) ){
                $this->subtract( $item_id, $quantity, $data );
                $q = $this->quantity();
                foreach( $this->newIds($quantity) as $serial ) $q->set( $serial, array() );
                $result = $this->add( $item_id, $q, $data );
            } else {
                $this->subtract($item_id, $quantity, $data );
                $result = $this->add( $item_id, Base::quantify( $quantity ), $data );
            }
            if( $local_txn ) {
                Transaction::commit();
            }
            return $result;       
    
        } catch( \Exception $e ){
            if( $local_txn) {
                if( Transaction::inProgress() ) Transaction::rollback();
            }
            $e = new Exception('cannot convert: ' . $e->getMessage(), $e->__toString() );
            throw $e;
        }  
    }
    
   /**
    * @see Stockpile_Interface::subtract();
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        $local_txn = Transaction::claimStart() ? TRUE : FALSE;
        try {
            if( ! $quantity instanceof Stockpile_HybridQuantity ) $quantity = $this->get( $item_id )->grab( $quantity );
            if( $quantity->value() < 1 ){
                throw $this->handle( new Stockpile_Exception('cannot subtract: invalid quantity', $quantity ) );
            }
            if( $quantity->tally() > 0 ){
                $tally = $this->core->subtract( $item_id, $quantity->tally(), $data );
            } else {
                $tally = $this->core->get($item_id, $with_lock = TRUE);
            }
            if( count( $quantity->all() ) > 0 ){
                $serial = $this->serial->subtract( $item_id, $quantity->all(), $data );
            } else { 
                $serial = $this->serial->get( $item_id);
            }
            if( $local_txn ) {
                Transaction::commit();
            }
            return $this->quantity( array('tally'=>$tally, 'serial'=>$serial ) );
        } catch( \Exception $e ){
            if( $local_txn) {
                if(Transaction::inProgress() ) Transaction::rollback();
            }
            $e = new Exception('cannot subtract: ' . $e->getMessage(), $e->__toString() );
            throw $e;
        }
    }
    
   /**
    * @see Stockpile_Interface::fetch();
    */
    public function fetch( array $item_ids = NULL ){
        $tally_result = $this->core->fetch( $item_ids );
        $serial_result = $this->serial->fetch( $item_ids );
        $ids = array_unique( array_merge( array_keys( $tally_result ), array_keys( $serial_result ) ) );
        $result = array();
        foreach( $ids as $item_id ){
            $serial_quantity = isset( $serial_result[ $item_id ] ) ? $serial_result[ $item_id ] : $this->serial->defaultQuantity();
            $tally_quantity = isset( $tally_result[ $item_id ] ) ? $tally_result[ $item_id ] : $this->core->defaultQuantity();
            $result[ $item_id ] = $this->defaultQuantity( array('tally'=>$tally_quantity, 'serial'=>$serial_quantity) );
        }
        return $result;
    }
    
   /**
    * @see Stockpile_Interface::defaultQuantity();
    */
    public function defaultQuantity( $v = NULL ){
        return new HybridQuantity( $v );
    }

   /**
    * @see Stockpile_Interface::coreType();
    */
    public function coreType(){
        return 'serial-tally';
    }
    
   /**
    * @see Stockpile_Interface::quantity();
    */
    public function quantity( $v = NULL ){
        return $this->defaultQuantity( $v );
    }
    
} // EOC


