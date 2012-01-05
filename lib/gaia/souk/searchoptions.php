<?php
namespace Gaia\Souk;

/**
 * @package GAIAONLINE
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/GAIAONLINE.txt
 */
class SearchOptions {
    
    protected static $sort_options = array(
        'low_price',
        'high_price',
        'expires_soon',
        'expires_soon_delay',
        'just_added',      
    );
    
    protected static $only_options = array(
        'buy',
        'bid',
    );

    protected $options = array('sort'=>'expires_soon', 'seller'=>NULL, 'buyer'=>NULL, 'closed'=>NULL, 'item_id'=>NULL, 'only'=>NULL, 'floor'=>NULL, 'ceiling'=>NULL);
    
    
    public function __construct( $options = NULL ){
        $this->import( $options );
    }
    
    public function import( $options = NULL ){
        if( $options == NULL ) return;
        if( $options instanceof self ) $options = $options->export();
        if( is_array( $options ) ) {
            foreach( $options as $k => $v ) $this->__set( $k, $v );
        }
    }

    public function export(){
        return $this->options;
    }

    public function __get( $k ){
        return isset( $this->options[ $k ] ) ? $this->options[ $k ] : NULL;
    }
    
    public function __isset( $k ){
        return isset( $this->options[ $k ] ) ? TRUE : FALSE;
    }
    
    public function __set( $k, $v ){
        if( is_array( $v ) ) sort( $v );
        return $this->options[$k] = is_scalar( $v ) ? strval( $v ) : $v;
    }
    
    public function __unset( $k ){
        unset( $this->options[ $k ] );
    }
}
//
