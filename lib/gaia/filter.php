<?php
namespace Gaia;

class Filter
{

    public static function posint( $value, $default = NULL ){
        return ctype_digit(strval($value)) ? $value : $default;
    }
    
    public static function int( $value, $default = NULL ){
        return (ctype_digit(strval($value)) ||
                      ($value[0] == '-' && ctype_digit(substr($value, 1)))
                      )
                 ? $value : $default;
    }
    
    public static function alpha( $value, $default = NULL ){
        return ctype_alpha(strval($value)) ? $value : $default;
    }
    
    public static function alphanum( $value, $default = NULL ){
        return ctype_alnum(strval($value)) ? $value : $default;
    }
    
    public static function numeric( $value, $default = NULL ){
        return is_numeric($value) ? $value : $default;
    }
    
    public static function raw( $value, $default = NULL ){
        return ( $value !== NULL ) ? $value : $default;
    }
    
    public static function bool( $value ){
            return (bool) $value ? TRUE : FALSE;
    }
    
    public static function enum( $value, $pattern, $default = NULL){
        return in_array($value, $pattern) ? $value : $default;
    }
    
    public static function regex( $value, $pattern, $default = NULL ){
        return preg_match($pattern, $value) ? $value : $default;
    }
    
    public static function utf8( $value, $default = NULL ){
        $value = UTF8::to( $value );
        if( $value === NULL ) $value = $default;
        return $value;
    }
    
    public static function safe( $value, $default = NULL ){
        // convert to utf8 safe string
        $value = self::utf8( $value );
        $unsafe = array('<', '>', '"', "'", '#', '&', '%', '{', '(');
        if( is_array( $value ) ){
            foreach( $value as $k=>$v ) $value[ $k ] = str_replace($unsafe, '', strval($v));
        } else {
            $value = str_replace($unsafe, '', strval($value));
        }
        
        
        // set to default value if there is nothing left after filtering
        return $value ? $value : $default;
    }
    
    public static function against($value, $filter, $default = NULL ) {
        if( is_object( $value ) ) return $value;
        $options = NULL;
        if ( is_array($filter) ) {
            $key = key($filter);
            switch($key ) {
            case 'regex':
            case 'enum':
                return call_user_func( array( __CLASS__, $key ), $value, $filter[ $key ], $default );
            default:
                $value = filter_var( $value, $key, $filter[ $key ] );
                    return $value ? $value : $default;
            }
        }
        if( ! method_exists( __CLASS__, $filter ) ){
            $value = filter_var( $value, $filter );
            return $value ? $value : $default;
        }
        return call_user_func( array( __CLASS__, $filter ), $value, $default );
    }
}
