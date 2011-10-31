<?php
namespace Gaia\Stockpile;

/**
 * Utility class. do not use directly.
 * used as a callback handler for sorting items by position.
 */
class CompareSort {
    
    /**
    * a list of positions keyed by item id
    */
    protected $list = array();
    
   /**
    * attach the list of positions to the class.
    */
    function __construct( array $l ){
        $this->list = $l;
    }
    
   /**
    * compare two item ids, based on their position in the list.
    * called by uksort
    * if no entry is found for an item, it goes to the bottom of the list.
    * if neither item is found on the list, compare their item ids.
    */
    function compare( $a , $b ){
        $found_a =  isset ($this->list[ $a ] );
        $found_b =  isset ($this->list[ $b ] );
        if( ! $found_a && ! $found_b ) {
            if( $a == $b ) return 0;
            return $a < $b ? 1 : -1;
        }
        if( ! $found_a ) return 1;
        if( ! $found_b ) return -1;
        if( $this->list[ $a ] == $this->list[$b] ) {
            if( $a == $b ) return 0;
            return $a < $b ? 1 : -1;
        }
        return $this->list[ $a ] < $this->list[ $b ] ? 1 : -1;
    }
} // EOC
