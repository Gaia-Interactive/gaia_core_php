<?php
namespace Gaia\Stockpile;
/**
* A quantity object that represents the stockpile hybrid result of tally and serial
*/
class HybridQuantity extends Quantity {
    
    protected $tally = 0;
    
    /**
    * take whatever we give it and try to import the value.
    * if it is an integer, it is a simple tally.
    * if it is an array of numbered keys, it is a list of serial properties, import it into serial.
    * if it is an array with the keys tally and serial, then it is trying to represents this current
    * data structure.
    * if it is a Stockpile_Quantity, import it into serials.
    * if it is an instance of this class, extract the data into the current structure.
    */
    public function import( $v = NULL ){
        if( $v === NULL ) return;
        if( $v instanceof self ){
            parent::import( $v->all() );
            $this->tally = $v->tally();
            return;
        }
        if( $v instanceof Quantity ){
            parent::import( $v->all() );
            return;
        }
        if( is_array( $v ) ){
            if( isset( $v['tally'] )){
                if( ! Base::validateInteger(  $v['tally'] ) ){
                    throw new Exception( 'invalid quantity',  $v['tally'] );
                }
                $this->tally =  $v['tally'];
                parent::import( isset( $v['serial'] ) ? $v['serial'] : NULL );
                return;
            }
            parent::import( $v );
            return;
        }
        if( ! Base::validateInteger( $v ) ){
            throw new Exception( 'invalid quantity', $v );
        }
        $this->tally = $v;
    }
   
   /**
    * an array structure that represents the data. keyed by tally and serial
    */
    public function export(){
        return array( 'tally'=>$this->tally(), 'serial'=>$this->all() );
    }
    
   /**
    * get only the tally count, not the serial count.
    */
    public function tally(){
        return $this->tally;
    }
    
   /**
    * get the current value which is a summary of the tally and serial inventories.
    */
    public function value(){
        return bcadd(parent::value(), $this->tally());
    }
    
   /**
    * extract a portion of the inventory out of the current class.
    */
    public function grab( $search ){
        $class = get_class( $this );
        if( Base::validatePositiveInteger($search) ) {
            if( $search > $this->value() ) {
                throw new Exception('not enough', $search );
            }
            if( $this->tally >= $search ) return new $class( $search );
            $search = $search - $this->tally;
            $serials = array_slice( $this->serials,  count( $this->serials ) - $search, $search, TRUE );
            return  new $class( array('tally'=>$this->tally, 'serial'=>$serials) );
        }
        
        if( is_array( $search ) ) {
            $v = new $class();
            $v->import( $search );
            $search = $v;
        }
        
        if( ! $search instanceof Quantity ){
            throw new Exception('invalid search to grab quantity');
        }
        
        $items = array();
        foreach( $search->serials() as $serial ){
            if( ! isset( $this->serials[ $serial ] ) ){
                throw new Exception('serial not found', $serial );
            }
            $items[ $serial ] = $this->serials[ $serial ];
        }
        $tally = 0;
        if( $search instanceof self ) $tally = $search->tally();
        if( $tally > $this->tally ) {
                throw new Exception('not enough', $search );
        }
        $result = new $class( $tally );
        $result->import( $items );
        return $result;
    }
} // EOF

