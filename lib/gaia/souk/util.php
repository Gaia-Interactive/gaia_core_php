<?php
namespace Gaia\Souk;

/**
* static method library for souk.
* Utility functions, constants, etc that need to be shared among the different souk classes.
*/
class Util {
  
   /**
    * when creating an auction, what is the longest time period an auction can be listed for?
    */
    const MAX_EXPIRE = 1209600; // two weeks
    
    /**
    * shortest time period an auction can be listed for.
    */
    const MIN_EXPIRE = 300;
    
    /**
    * Don't return more than 1k records from a search. after that, it is just useless pagination.
    */
    const SEARCH_LIMIT = 1000;
    
    
    /**
    * what are valid fields declared in souk?
    */
    protected static $fields = array(
        'id', 
        'item_id', 
        'price', 
        'quantity', 
        'closed', 
        'seller', 
        'buyer', 
        'bid', 
        'proxybid', 
        'bidder', 
        'step', 
        'bidcount', 
        'reserve', 
        'created', 
        'expires', 
        'touch');
    
   /**
    * utility method for returning the fields in souk.
    */
    public static function fields(){
        return self::$fields;
    }
     
   /**
    * Parse an id, which is made up of 2 parts ... the shard and the row id.
    */
    public static function parseId( $id, $validate = TRUE ){
        $id = strval( $id );
        if( strlen( $id ) > 16 && ctype_digit( $id ) ){
            $shard = substr( $id, 0, 6 );
            $row_id = substr( $id, 6);
            return array( $shard, ltrim($row_id, '0'));
        }
        if( $validate ) {
            throw new Exception('invalid id', $id );
        }
        return array(NULL, NULL );
    }
    
    /**
    * create an auction id out of the shard and row id.
    */
    public static function composeId( $shard, $row_id ){
        if( strlen( $shard ) != 6 || ! ctype_digit( strval( $shard ) ) || ! ctype_digit( strval( $row_id ) ) ) return NULL;
        return $shard . str_pad($row_id, 11, '0', STR_PAD_LEFT);
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
    * returns the current time.
    */
    public static function now(){
        return \Gaia\Time::now();
    }
    
   /**
    * create a new auction listing.
    */
    public static function listing( $v ){
        return new Listing( $v );
    }

    /**
    * factory method for creating search options out of params in an array.
    */
    public static function searchOptions( $options ){
        if( $options instanceof SearchOptions ) return $options;
        return new SearchOptions( $options );
    }
    
    /**
    * validate a listing. 
    * placed here so that other classes and application code can validate a listing
    * if need be, external to souk itself.
    */
    public static function validateListing( $l ){
        $listing = self::listing( $l );
        $now = self::now();
        if( ! $listing->expires ) $listing->expires = $now + self::MAX_EXPIRE;
        
        if( ! self::validatePositiveInteger( $listing->expires ) ){
            throw new Exception('expires invalid', $listing->expires );
        }
        
        if( $listing->expires > $now + self::MAX_EXPIRE ){
            throw new Exception('expires too big', $listing->expires );
        }
        
        if( $listing->expires < $now + self::MIN_EXPIRE ){
            throw new Exception('expires too small', $listing->expires );
        }
        
        if( ! $listing->created ) $listing->created = $now;
        
        if( ! self::validatePositiveInteger( $listing->created ) ){
            throw new Exception('created invalid', $listing->created );
        }
        if( $listing->created > $now ){
            throw new Exception('created too big', $listing->created );
        }
        
        if( ! isset( $listing->price ) ) $listing->price = 0;
        if( ! self::validateInteger( $listing->price ) ){
                throw new Exception('price invalid', $listing->price );
        }
        
        if( ! $listing->price && ! isset( $listing->bid ) ) $listing->bid = 0;
        if( ! $listing->quantity ) $listing->quantity = 1;
        if( ! self::validatePositiveInteger( $listing->quantity ) ){
            throw new Exception('quantity invalid', $listing->quantity );
        }
        
        if( isset( $listing->bid ) ){
            if( ! $listing->step ) $listing->step = 1;
            if( ! self::validatePositiveInteger( $listing->step ) ){
                throw new Exception('step invalid', $listing->step );
            }
            if( ! $listing->reserve ) $listing->reserve = 1;
            if( ! self::validatePositiveInteger( $listing->reserve ) ){
                throw new Exception('reserve invalid', $listing->reserve );
            }
        } else {
            $listing->bid = 0;
            $listing->step = 0;
            $listing->reserve = 0;
        }
        
        if( ! isset( $listing->item_id ) ) $listing->item_id = 0;
        
        if( ! self::validateInteger( $listing->item_id ) ){
            throw new Exception('item_id invalid', $listing->item_id );
        }
        
        $listing->touch = 0;
        $listing->closed = 0;
        $listing->buyer = 0;
        $listing->bidder = 0;
        $listing->touch = 0;
        $listing->bidcount = 0;
        $listing->proxybid = 0;

        return $listing;
    }
    
    public static function dateshard(){
        return new \Gaia\Shard\Date( array('by'=>'week', 'cutoff'=>'21') );
    }
}

// EOC
