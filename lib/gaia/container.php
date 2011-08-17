<?php
namespace Gaia;

class Container {

    protected $_data = array();
    
    function __construct( $data = NULL ){
        if( is_array( $data ) ) $this->_data = $data;
        if( $data instanceof Container ) $this->_data = $data->getAllData();
    }

    function & setByRef($name, &$value){
        return $this->_data[$name] =& $value;
    }
    
    public function increment($name, $value = 1) {
        if(! isset($this->_data[$name]) ) $this->_data[$name] = 0;
        return $this->_data[$name] += $value;
    }
    
    function append($name, $value){
        if( ! isset($this->_data[$name]) ) return $this->_data[$name] = array($value);
        if( is_scalar($this->_data[$name]) ) return $this->_data[$name] .= $value;
        if( ! is_array($this->_data[$name]) ) return $this->_data[$name] = array($value);
        return $this->_data[$name][] = $value;
    }
    
    function get($name){
        return ( isset( $this->_data[$name] ) ) ? $this->_data[ $name ]: NULL;
    }
    
    function remove( $name ){
       unset( $this->_data[ $name ] );
    }
    
    function exists($name){
        return isset($this->_data[ $name ]) ? TRUE : FALSE;
    }
    
     function isEmpty( $name ){
        if( ! isset( $this->_data[$name] ) ) return TRUE;
        return empty( $this->_data[$name] ) ? TRUE : FALSE;
     }
}
