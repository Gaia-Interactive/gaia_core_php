<?php
namespace Gaia\Store;

/**
 * pass variables in the container through input filters to make sure they are safe. 
 */
class Filter extends Wrap
{
    
    /**
    * wrapper around the container accessor ... filtering the input values.
    * by default all values are filtered. you can access the raw data by doing:
    *   $request->get( $key, 'raw');
    */
    public function get( $key, $filter = 'safe', $default = NULL ){
        if( is_array( $key ) ){
            $res = array();
            foreach( $this->core->get($key) as $k =>$v ){
                $v = $this->filter( $v, $filter, $default );
                if( $v === NULL ) continue;
                $res[ $k ] = $v;
            }
            if( $default !== NULL ){
                foreach( $key as $k ){
                    if( ! isset( $res[ $k ] ) ) $res[ $k ] = $default;
                }
            }
            return $res;
        }
        return $this->filter( $this->core->get( $key ), $filter, $default );
    }

    public function __get( $k ){
        return $this->get( $k );
    }
    
    public static function filter($value, $filter = 'safe', $default = NULL ) {
        return \Gaia\Filter::against( $value, $filter, $default );
    }
}
