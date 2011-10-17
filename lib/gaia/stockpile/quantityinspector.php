<?php
namespace Gaia\Stockpile;

/**
 * A wrapper which allows us to sanitize quantities
 * as they come in and out.
 */
class QuantityInspector extends Passthru {
   
   /**
    * a sanitizing callback function for quantity.
    */
    protected $sanitizer = NULL;
    
   /**
    * passthru constructor ... 
    * allows us to attach a callback method
    * @param core
    * @param callback
    */
    public function __construct( Iface $core, $callback ){
        parent::__construct( $core );
        if( ! is_callable( $callback ) ) {
            throw $this->handle( new Exception('invalid sanitizer', $callback ) );
        }
        $this->sanitizer = $callback;
    }
    
   /**
    * @see Stockpile_Interface::add();
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        $quantity = $this->quantity( $quantity );
        call_user_func( $this->sanitizer, $quantity );
        $res = $this->core->add( $item_id, $quantity, $data );
        call_user_func( $this->sanitizer, $res );
        return $res;
    }
    
   /**
    * @see Stockpile_Interface::subtract();
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        $quantity = $this->quantity( $quantity );
        call_user_func( $this->sanitizer, $quantity );
        $res = $this->core->subtract( $item_id, $quantity, $data );
        call_user_func( $this->sanitizer, $res );
        return $res;
    }
    
   /**
    * @see Stockpile_Interface::fetch();
    */
    public function fetch( array $item_ids = NULL ){
        $res = $this->core->fetch( $item_ids );
        foreach( $res as $item_id => $quantity ){
            call_user_func( $this->sanitizer, $quantity );
        }
        return $res;
    }
 
} // EOC

