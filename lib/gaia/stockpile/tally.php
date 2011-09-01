<?php
namespace Gaia\Stockpile;
/**
 * Core functionality of the stockpile class.
 * Bare-Bones, no caching, no logging. But everything works!
 * Very lightweight. And you can decorate it any way you want -- change out the logging or the caching.
 */
class Tally extends Base {

   /**
    * @see Stockpile_Interface::add()
    * validate item id and quantity, same rules apply for both.
    * gotta be integers.
    * the dao transforms the insert into an increment on key collision.
    * this dao has a special mechanism to return the new value for this item, once the
    * insert/update has gone through.
    * this is very lightweight since it only involves asking the db to return an in-memory variable.
    * the value returned is the new quantity in your inventory, once the new quantity has been applied.
    * we use this value to do logging and caching.
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        if( $quantity instanceof Quantity ) $quantity = $quantity->value();
        foreach( array('item_id'=>$item_id, 'quantity' => $quantity ) as $k => $v ){
            if( ! self::validatePositiveInteger( $v ) )  {
                throw $this->handle( new Exception('cannot add: invalid ' . $k, $v ) );
            }
        }
        try {
            return $this->storage('tally')->add( $item_id, $quantity );
        } catch( Exception $e ){
            throw $this->handle( $e );
        }
    }
   /**
    * @see Stockpile_Interface::subtract();
    * loop through and validate item id and quantity, same rules apply for both.
    * gotta be an integer.
    * since we are subtracting, the value has to be there.
    * will return no rows affected, if it isn't there.
    * don't apply the update unless we have enough to cover it.
    * make sure the quantity is big integer safe.
    * roll back the transaction if there is too little to subtract the amount.
    * this dao has a special mechanism to return the new value for this item, once the
    * update has gone through.
    * the value returned is the new quantity in your inventory, once the new quantity has been applied.
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        if( $quantity instanceof Stockpile_Quantity ) $quantity = $quantity->value();
        foreach( array('item_id'=>$item_id, 'quantity' => $quantity ) as $k => $v ){
            if( ! self::validatePositiveInteger( $v ) )  {
                throw $this->handle( new Exception('cannot subtract: invalid ' . $k, $v ) );
            }
        }
        try {
            return $this->storage('tally')->subtract( $item_id, $quantity );
        } catch( Exception $e ){
            throw $this->handle( $e );
        }
    }
    
   /**
    * @see Stockpile_Interface::fetch()
    * if items were passed in, search by item_id
    * we only want items with actual quantities in them.
    * eliminate empty rows.
    * sort the list by item id, so we are consistent.
    */
    public function fetch( array $item_ids = NULL, $with_lock = FALSE ){
        $with_lock = ( $with_lock && $this->inTran()) ? TRUE : FALSE;
        if( is_array( $item_ids ) ) {
            if( count( $item_ids ) < 1 ) throw $this->handle( new Exception('cannot read: no item_ids') );
            foreach( $item_ids as $id ) {
                if( ! self::validatePositiveInteger( $id ) )  {
                    throw $this->handle( new Exception('cannot read: invalid item_id', $id ) );
                }
            }
        } 
        $list = $this->storage('tally')->fetch( $item_ids, $with_lock );
        ksort( $list, SORT_NUMERIC );
        return $list;
    }
    
   /**
    * tell everyone we are a tally object. used by Transfer so it knows how to validate.
    */
    public function coreType(){
        return 'tally';
    }
    
} // EOC

