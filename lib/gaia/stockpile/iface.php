<?php
namespace Gaia\Stockpile;

/**
 * Interface for Stockpile. All of the wrappers, and the core conform to this api.
 */
interface Iface  {
    
   /**
    * retrieve a single item id quantity if you pass in a single key.
    * if you pass in array, get an array back of key/value pairs.
    */
    public function get( $item );
    
   /**
    * retrieve all of the items in the user's inventory.
    */
    public function all();
    
   /**
    * low level mechanism for fetching data.
    * polymorphic function ... behaves differently depending on how you call it.
    * passing in null, or a list of item ids.
    * apps probably don't want to call directly, but some of the wrapper classes do.
    */
    public function fetch( array $item_ids = NULL );
    
   /**
    * @return   int     user_id
    */
    public function user();
    
   /**
    * @return   string     app
    */
    public function app();
    
   /**
    * add a given quantity (defaults to 1) to an item id.
    * store the value in the database, and return the current count once the 
    * value has been applied.
    * @param    int     item_id
    * @param    int     quantity, optional ... defaults to 1.
    * @param    array   optional data to pass along. used mostly for logging, for example.
    * @return   int     total number this user now has in peristent storage for this item.
    */
    public function add( $item_id, $quantity = 1, array $data = NULL );
    
   /**
    * subtract a given quantity (defaults to 1) from an item id.
    * store the value in the database, and return the current count once the 
    * value has been applied.
    * Throws an exception if there isn't enough left to subtract the amount.
    * @param    int     item_id
    * @param    int     quantity, optional ... defaults to 1.
    * @param    array   optional data to pass along. used mostly for logging, for example.
    * @return   int     total number this user now has in peristent storage for this item.
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL );
    
    
   /**
    * set the item to a given quantity.
    * store the value in the database, and return the current count once the 
    * value has been applied.
    * Throws an exception if there is a problem
    * @param    int     item_id
    * @param    int     quantity
    * @param    array   optional data to pass along. used mostly for logging, for example.
    * @return   int     total number this user now has in peristent storage for this item.
    */
    public function set( $item_id, $quantity, array $data = NULL );
    
   /**
    * utility method to determine what is at the core of this object.
    * will be either tally or serial. 
    * Used by transfer primarily so it can do validation and sanitization.
    */
    public function coreType();
    
   /**
    * return a quantity appropriate for the current type of inventory.
    */
    public function quantity( $v = NULL );
    
    
   /**
    * if an exception is encountered, make sure we roll back the transaction.
    * return $e;
    */
    public function handle( \Exception $e );
    
    
} // EOC
