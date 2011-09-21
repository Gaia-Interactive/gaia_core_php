<?php
namespace Gaia\Stockpile;
use \Gaia\DB\Transaction;
use \Gaia\Exception;
/**
 * Core functionality of the stockpile class.
 * Bare-Bones, no caching, no logging. But everything works!
 * Very lightweight. And you can decorate it any way you want -- change out the logging or the caching.
 */
class Base implements Iface {

   /**
    * @string   the application id. must be lowercase alphanumeric (underscore okay)
    */
    protected $app;
    
   /**
    * @int      the user id  
    */
    protected $user_id;
    
    /**
    * for test purposes to simulate passage of time.
    *
    */
    public static $time_offset = 0;
    
    protected static $id_generator;
    
   /**
    * class constructor.
    * @param string                     app
    * @param int                        user_id
    */
    public function __construct( $app, $user_id){
        if( ! self::validateApp( $app ) ) {
            throw $this->handle( new Exception('invalid app', $app) );
        }
        if( ! self::validatePositiveInteger( $user_id ) ) {
            throw $this->handle( new Exception('invalid user_id', $user_id) );
        }
        $this->app = $app;
        $this->user_id = $user_id;
    }
    
   /**
    * @see Stockpile_Interface::user()
    */
    public function user(){
        return $this->user_id;
    }
    
   /**
    * @see   Stockpile_Interface::app()
    */
    public function app(){
        return $this->app;
    }

   /**
    * @see Stockpile_Interface::add()
    */
    public function add( $item_id, $quantity = 1, array $data = NULL ){
        return 0;
    }
    
   /**
    * @see Stockpile_Interface::subtract();
    */
    public function subtract( $item_id, $quantity = 1, array $data = NULL ){
        return 0;
    }
    
   /**
    * @see Stockpile_Interface::set();
    * wrapped in a transaction so that if it gets interrupted mid-way through, it rolls back.
    */
    public function set( $item_id, $quantity, array $data = NULL ){
        Transaction::start();
        try {
            $current = $this->get($item_id);
            if( Base::quantify( $current ) > 0 ) $this->subtract( $item_id, $current, $data );
            $result = $this->add( $item_id, $quantity, $data );
            if( ! Transaction::commit() ) throw new Exception('database error');
            return $result;
        } catch( \Exception $e ){
            $this->handle( $e );
            throw $e;
        }
    }
    
   /**
    * @see Stockpile_Interface::get()
    */
    public function get( $item, $with_lock = FALSE ){
        $ids = ( $scalar =  is_scalar( $item ) ) ? array( $item ) : $item;
        $rows = $this->fetch( $ids, $with_lock  );
        if( ! $scalar ) return $rows;
        return is_array( $rows ) && isset( $rows[ $item ] ) ? $rows[ $item ] : $this->defaultQuantity();
    }
    
   /**
    * @see Stockpile_Interface::all()
    */
    public function all(){
        return $this->fetch();
    }
    
   /**
    * @see Stockpile_Interface::fetch()
    */
    public function fetch( array $item_ids = NULL ){
        return array();
    }
    
   /**
    * if an exception is thrown, make sure we roll back the transaction.
    */
    public function handle( \Exception $e ){
        if( Transaction::inProgress() ) Transaction::rollback();
        return $e;
    }

    /**
    * what to return when nothing found. overridden in itemize class.
    *
    */
    public function defaultQuantity( $v = NULL ){
        return $this->quantity( $v );
    }
    
   /**
    * base doesn't have a core type.
    */
    public function coreType(){
        return NULL;
    }

    /**
    * @see Stockpile_Interface::quantity();
    */
    public function quantity( $v = NULL ){
        return self::validatePositiveInteger( $v ) ? $v : '0';
    }
    
   /**
    * make sure the app string is valid. we use it in the database so be careful.
    */
    public static function validateApp( $v ){
        return ( is_scalar( $v ) && preg_match('/^[a-z0-9_]+$/', $v) ) ? TRUE : FALSE;
    }
    
   /**
    * make sure this is actully an integer
    */
    public static function validateInteger( $v ){
        return is_scalar( $v ) && ctype_digit( strval( $v ) ) ? TRUE : FALSE;
    }
    
    /**
    * make sure the value is a positive integer greater than 0
    */
    public static function validatePositiveInteger( $v ){
        return self::validateInteger( $v ) && substr($v, 0, 1) != '0' ? TRUE : FALSE;
    }
    
   /**
    * generate a single unique id
    * when generating serials using unixtime as the prefix, subtract the ID_OFFSET amount from
    * the start time. gives us more breathing room for using serial numbers.
    * unix_timestamp of 1000000000 == 2001/09/08 18:46:40
    * since the current time won't ever be that number, it is a safe point to go back to.
    * shouldn't hit big int max for 80 years. at that point we'll rewrite our app :)
    */
    public static function newId(){
         return self::idGenerator()->id();
    }
    
   /**
    * return a list of new ids
    */
    public static function newIds( $ct = 1 ){
        return self::idGenerator()->ids( $ct );
    }
    
    public static function attachIdGenerator( \Gaia\NewId\Iface $generator ){
        return self::$id_generator = $generator;
    }
    
    public static function idGenerator(){
        if( isset( self::$id_generator ) ) return self::$id_generator;
        return self::$id_generator = new \Gaia\NewID\TimeRand;
    }
    
   /**
    * return the value of a given quantity.
    */
    public static function quantify( $v ){
        if( is_scalar( $v ) ) return $v;
        if( $v instanceof Quantity ) return $v->value();
        return strval( $v );
    }
    
    public static function inTran(){
        return Transaction::atStart() ? FALSE : TRUE;
    }

    public function storage($name){
        return Storage::get( $this, $name );
    }
    
    public static function time(){
        return time() + self::$time_offset;
    }
} // EOC

