<?php
namespace Gaia\ShortCircuit;

class Input extends \Gaia\Container
{
    public function get( $key, $filter = 'safe', $default = NULL ){
        if( is_array( $key ) ){
            $res = array();
            foreach( parent::get($key) as $k =>$v ){
                $v = \Gaia\Filter::against( $v, $filter, $default );
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
        return \Gaia\Filter::against( parent::get( $key ), $filter, $default );
    }
}
