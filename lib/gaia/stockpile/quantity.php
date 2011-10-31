<?php
namespace Gaia\Stockpile;

/**
 * Stockpile_Serial class uses this class to represent quantities, where the result can't be described
 * by a simple integer the way tally results can.
 * this class gives you a count of a given item, but also allows you to drill down and look at properties
 * attached to individual instances of that item. The cache will write this whole object into the 
 * cache in place of the quantity integer.
 */
class Quantity {
    
    /**
    * key/value pairs of serial/properties.
    */
    protected $serials = array();
    
   /**
    * create a new quantity object.
    * accepts a variety of inputs. don't you just love polymorphic behaviors? let's go over them.
    * if you pass in NULL, nothing happens.
    * if you pass in an array of serial numbers, it populates those serial numbers as keys with empty properties.
    * this is useful in cases where you are transferring serials from one account to another.
    * if you pass in an array of serial -> property pairs, it populates both the serials and the properties
    * into this object.
    * You get similar behavior if you pass in another quantity object. it copies all the serial/property pairings in.
    */
    public function __construct( $v = NULL ){
        $this->import( $v );
    }
    
    public function import( $v = NULL ){
        if( $v === NULL ) return;
        if( $v instanceof self ) $v = $v->all();
        if( is_array( $v ) ){
            foreach( $v as $serial => $properties ){
                if( is_scalar( $properties ) ) {
                    $this->set( $properties, array() );
                } else {
                    $this->set( $serial, $properties );
                }
            }
        }
    }
    
    /**
    * a utility method for getting the contents of this quantity.
    * pass this same value back into a new object constructor and you get the same object back.
    */
    public function export(){
        return $this->all();
    }
    
   /**
    * allocate a new serial and associate the given properties with it.
    * on Stockpile_Serial::add    the serial / properties will be inserted.
    */
    public function add( array $properties ){
        return $this->serials[ Base::newId() ] = $properties;
    }
    
   /**
    * get the properties for a specific serial entry.
    */
    public function get( $serial ){
        return isset( $this->serials[ $serial ] ) ? $this->serials[ $serial ] : NULL;
    }
    
   /**
    * Write a serial/properties pairing into the object.
    * automatically re-sorts the serials in order of the serials.
    * keeps the object consistent so it can be compared easily.
    */
    public function set( $serial, array $properties ){
        if( ! is_scalar( $serial ) || ! ctype_digit( strval( $serial ) ) ) {
            throw new Exception('invalid serial', $serial);
        }
        if( ! is_array( $properties ) ) throw new Exception('invalid property', $properties);
        $sort = isset( $this->serials[ $serial ] ) ? TRUE : FALSE;
        $this->serials[ $serial ] = $properties;
        if( $sort ) ksort( $this->serials, SORT_NUMERIC);
        return $this->serials[ $serial ];
    }
    
   /**
    * return all of the data associated with this quantity.
    */
    public function all(){
        return $this->serials;
    }
    
   /**
    * a list of all serials assoc. with this quantity.
    */
    public function serials(){
        return array_keys( $this->serials );
    }
    
   /**
    * a numeric representation of the quantity. in other words, how many serials we have.
    */
    public function value(){
        return count( $this->serials );
    }
    
   /**
    * check to see if a given serial exists or not.
    */
    public function exists( $serial ){
        return isset( $this->serials[ $serial ] ) ? TRUE : FALSE;
    }
    
   /**
    * remove a serial from the quantity, and all the properties associated with it.
    */
    public function remove( $serial ){
        unset( $this->serials[ $serial ] );
    }
    
   /**
    * an internal method used to grab a subset of the serial/property information stored here.
    * another beautiful polymorphic function.
    * if you pass in an integer, it will grab that many of the serials and the properties
    * and wrap them up in a new quantity class and return it for you.
    * if you pass in an array, it searches for those specific serial numbers.
    * if you pass in a quantity object, it will extract those serials and look for those serials.
    * this function probably needs to be profiled and optimized.
    */
    public function grab( $search ){
        $class = get_class( $this );
        if( Base::validatePositiveInteger($search) ) {
            $serial_ct = count( $this->serials );
            if( $search > $serial_ct ) {
                throw new Exception('not enough serials', $search );
            }
            $offset = $serial_ct - $search;
            $length = $search;
            return new $class( array_slice( $this->serials, $offset, $length, TRUE ) );
        }
        
        if( is_array( $search ) ) {
            $search = new $class( $search );
        }
        
        if( ! $search instanceof self ){
            throw new Exception('invalid search to grab quantity');
        }
        $items = array();
        foreach( $search->serials() as $serial ){
            if( ! isset( $this->serials[ $serial ] ) ){
                throw new Exception('serial not found', $serial );
            }
            $items[ $serial ] = $this->serials[ $serial ];
        }
        return new $class( $items );
    }
    
   /**
    * sneaky way of converting the object into a scalar quantity.
    */
    public function __toString(){
        return strval( $this->value() );
    }
}
