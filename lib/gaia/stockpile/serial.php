<?php
namespace Gaia\Stockpile;

/**
 * Serialized item inventory ... allows you to attach properties
 * Bare-Bones, no caching, no logging.
 */
class Serial extends Base {
    
   /**
    * @see IFace::add()
    * add new serial rows to the stockpile serial table for the user.
    * has to converta numeric quantity into a stockpile quantity object so that it can
    * generate the serials needed for the inserts and map properties into it.
    * duplicate key collisions are handled and allow you to overwrite existing serials in the 
    * inventory. this is how you change data.
    * might seem weird to change the data using an add ... maybe alias the function with change,
    * and do a serial check in that method first to verify the data exists?
    * this should do the job for now.
    * can't add if no quantiy is available. checks for that.
    * wraps the whole thing in a batch insert so the operation happens as an autonomous step.
    * that is how we can get away with doing this operation with no transaction.
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        $quantity = $this->quantity( $quantity );
        if( ! self::validatePositiveInteger( $item_id ) )  {
            throw $this->handle( new Exception('cannot add: invalid item_id', $item_id ) );
        }
        if( $quantity->value() < 1 ){
            throw $this->handle( new Exception('cannot add: invalid quantity', $quantity ) );
        }
        try {
            $this->storage('serial')->add( $item_id, $quantity );
        } catch( \Exception $e ){
            throw $this->handle( $e );
        }
        return $this->get( $item_id );
    }
    
   /**
    * @see Stockpile_Interface::subtract();
    * delete 1 or more rows out of the serials table.
    * does a validity check to make sure you have the items.
    * when in a transaction, we need to do a select for update approach to hold a row lock on the serials 
    * we are deleting.
    * otherwise, just delete the rows, and throw an exception if the affected rows doesn't match up
    * with what we expect.
    * really should always use a transaction when manipulating serials since there are multiple rows
    * affected, but still reasonably safe since it verifies you have the rows first.
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        if( ! $quantity instanceof Stockpile_Quantity ){
            try {
                $quantity = $this->get( $item_id )->grab( $quantity );
            } catch ( Exception $e ){
                throw $this->handle( new Exception('cannot subtract: ' . $e->getMessage(), $e->__toString() ) );
            }
        }
        if( $quantity->value() < 1 ) throw $this->handle( new Exception('cannot subtract: invalid quantity', $quantity ) );
        $vserials = $this->verifySerials( $item_id, $quantity->serials());
        if( count( $vserials ) < 1 ) throw $this->handle( new Exception('not enough left to subtract', $item_id ) );
        $serials = $quantity->serials();
        if( array_intersect( $serials, $vserials ) != $serials ){
            throw $this->handle( new Exception('serial doesn\'t exist in inventory', $serials ) );
        }
        try {
            $ct = $this->storage('serial')->subtract( $item_id, $quantity->serials() );
        } catch( \Exception $e ){
            throw $this->handle( $e );
        }
        if( $ct < $quantity->value() ) throw $this->handle( new Exception('not enough left to subtract', $ct ) );
        return $this->get($item_id);
    }
    
   /**
    * @see Stockpile_Interface::fetch()
    * grab all the serial rows by item id and populate them into a list sorted by item id.
    * the quantity value is a quantity object that stores all the information about the properties of
    * the serials associated with that item.
    * so far we don't really allow you to search by serial ... you have to know the item id, and then
    * you can drill down and get serial information by using the quantity object ->get( $serial );
    */
    public function fetch( array $item_ids = NULL ){
        if( is_array( $item_ids ) ) {
            if( count( $item_ids ) < 1 ) throw $this->handle( new Exception('cannot read: no item_ids' ) );
            foreach( $item_ids as $id ) {
                if( ! self::validatePositiveInteger( $id ) )  {
                    throw $this->handle( new Exception('cannot read: invalid item_id', $id ) );
                }
            }
        }
        try {
            $list = $this->storage('serial')->fetch( $item_ids );
        } catch( \Exception $e ){
            throw $this->handle( $e );
        }
        foreach( $list as $item=>$quantity ){
            $list[ $item ] = $this->defaultQuantity( $quantity );
        }
        ksort( $list, SORT_NUMERIC );
        return $list;
    }
    
   /**
    * This function may seem to duplicate some logic in fetch, but it is important to separate them out
    * to keep this methodology lightwieght and simply about locking the rows. We want the locking
    * mechanism to be as light as possible. If we have to fetch the properties over the wire, the db
    * has to do more work than it needs to and so does the app. Most likely the properties already came
    * over from the cache in the quantity object earlier.
    */
    protected function verifySerials( $item_id, array $serials ){
        if( count( $serials ) < 1 ) 
            throw $this->handle( new Exception('not enough left to subtract', $item_id ) );
        try {
            return $this->storage('serial')->verifySerials( $item_id, $serials );
        } catch ( Exception $e ){
            throw $this->handle( $e );
        }
    }
    
   /**
    * This method is used to determine what is at the core of a given stockpile stack.
    * allows us to change behavior slightly depending on whether it is tally or serial.
    * for the most part, the interface works the same.
    */
    public function coreType(){
        return 'serial';
    }

   /**
    * Create a quantity object based off of user defined params.
    * generate serials if need be.
    */
    public function quantity( $quantity = NULL ){
        if( self::validatePositiveInteger( $quantity ) ){
            if( $quantity > 10000 ){
                throw $this->handle( new Exception('quantity too large', $quantity ) );
            }
            return $this->defaultQuantity( Base::newIds( $quantity ) );
        } else {
            return $this->defaultQuantity( $quantity );
        }
    }
    
    /**
    * what to return when nothing found.
    */
    public function defaultQuantity( $v = NULL ){
        return new Quantity( $v );
    }
 
} // EOC

