<?php
namespace Gaia\NewID;

class TimeRand implements Iface {

    const ID_OFFSET = '1000000000';
    
    /**
    * for test purposes to simulate passage of time.
    *
    */
    public static $time_offset = 0;

   /**
    * generate a single unique id
    * when generating ids using unixtime as the prefix, subtract the ID_OFFSET amount from
    * the start time. gives us more breathing room for using serial numbers.
    * unix_timestamp of 1000000000 == 2001/09/08 18:46:40
    * since the current time won't ever be that number, it is a safe point to go back to.
    * shouldn't hit big int max for 80 years. at that point we'll rewrite our app :)
    */
    public function id(){
        $prefix = bcsub( self::time(), self::ID_OFFSET );
        if( $prefix < 1 ) throw new Exception('invalid serial generated', $prefix );
        return $prefix . str_pad( mt_rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
    }
    
   /**
    * return a list of new ids
    */
    public function ids( $ct = 1 ){
         $ids = array();
         if( $ct < 1 ) $ct = 1;
         while( $ct-- > 0 ) $ids[] = $this->id();
         return $ids;
    }
    
    public static function time(){
        return time() + self::$time_offset;
    }
}
