<?php
namespace Gaia\Souk;

/**
 * @package GAIAONLINE
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/GAIAONLINE.txt
 */
class Listing {

    protected $listing = array();
    protected $prior = NULL;
    
    public function __construct( $listing = NULL ){
        $this->listing = array_fill_keys( Util::fields(), NULL );
        $this->import( $listing );
    }
    
    public function import( $listing = NULL ){
        if( $listing == NULL ) return;
        if( $listing instanceof self ) $listing = $listing->export();
        if( is_array( $listing ) ) {
            foreach( $listing as $k => $v ) $this->__set( $k, $v );
        }
    }

    public function export(){
        return $this->listing;
    }
    
    public function setPriorState( Listing $listing ){
        $this->prior = new self( $listing );
    }
    
    public function priorstate(){
        return isset( $this->prior ) ? $this->prior : NULL;
    }
    
    public function keys(){
        return array_keys( $this->listing );
    }
    
    public function __get( $k ){
        return isset( $this->listing[ $k ] ) ? $this->listing[ $k ] : NULL;
    }
    
    public function __isset( $k ){
        return isset( $this->listing[ $k ] ) ? TRUE : FALSE;
    }
    
    public function __set( $k, $v ){
        return $this->listing[$k] = is_scalar( $v ) ? strval( $v ) : $v;
    }
    
    public function __unset( $k ){
        unset( $this->listing[ $k ] );
    }
}
//
