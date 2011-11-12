<?php
namespace Gaia\NewID;

class Callback implements Iface {
    
    protected $callback;

    public function __construct( $callback ){
        if( ! is_callable( $callback ) ) trigger_error('invalid callback', E_USER_ERROR);
        $this->callback = $callback;
    }
    
    public function id(){
        $id = strval( call_user_func( $this->callback ) );
        if( ! ctype_digit( $id ) ) throw new Exception('invalid id');
        return $id;
    }
    
   /**
    * return a list of new ids
    */
    public function ids( $ct = 1 ){
         $ids = array();
         if( $ct < 1 ) $ct = 1;
         while( $ct-- > 0 ) $ids[] = self::id();
         return $ids;
    }
}
