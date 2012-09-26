<?php
namespace Gaia\Store;

/**
* The wrap class implements the interface and allows us to pass calls inward to a core
* object with the same interface. This allows us to intercept the calls on their way inward if 
* we want and change the behavior.
*
* We can append a namespace to the key, or do key replicas, or any number of other tricks. 
*/
class Validator extends Wrap {
    
    protected $validators = array();
    
    public function __construct( Iface $core,  array $validators ){
        parent::__construct( $core );
        foreach( $validators as $method => $validator ) {
            if( ! $validator instanceof \Closure ) continue;
            $this->validators[ $method ] = $validator;
        }
    }
    
    public function add( $key, $value, $expires = 0){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $key, $value, $expires ) ) return FALSE;
        }
        return parent::add( $key, $value, $expires );
    }
    
    public function set( $key, $value, $expires = 0){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $key, $value, $expires ) ) return FALSE;
        }
        return parent::set( $key, $value, $expires );
    }
    
    public function replace( $key, $value, $expires = 0){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $key, $value, $expires ) ) return FALSE;
        }
        return parent::replace( $key, $value, $expires );
    }
    
    public function increment( $key, $value = 1 ){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $key, $value ) ) return FALSE;
        }
        return parent::increment( $key, $value );
    }
    
    public function decrement( $key, $value = 1 ){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $key, $value ) ) return FALSE;
        }
        return parent::decrement( $key, $value );
    }
    
    public function get( $input ){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $input ) ) return FALSE;
        }
        return parent::get( $input );
    }
    
    public function delete( $key ){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate( $key ) ) return FALSE;
        }
        return parent::delete( $key );
    }
    
    public function flush(){
        if( isset( $this->validators[ __FUNCTION__ ] ) ) {
            $validate = $this->validators[ __FUNCTION__ ];
            if( ! $validate() ) return FALSE;
        }
        return parent::flush();
    }
}