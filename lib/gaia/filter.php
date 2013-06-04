<?php
namespace Gaia;

class Filter
{

    public static function posint( $value ){
        return ctype_digit(strval($value)) ? $value : NULL;
    }
    
    public static function int( $value ){
        return (ctype_digit(strval($value)) ||
                      ($value[0] == '-' && ctype_digit(substr($value, 1)))
                      )
                 ? $value : NULL;
    }
    
    public static function alpha( $value ){
        return ctype_alpha(strval($value)) ? $value : NULL;
    }
    
    public static function alphanum( $value ){
        return ctype_alnum(strval($value)) ? $value : NULL;
    }
    
    public static function numeric( $value ){
        return is_numeric($value) ? $value : NULL;
    }
    
    public static function raw( $value ){
        return $value;
    }
    
    public static function bool( $value ){
            return (bool) $value ? TRUE : FALSE;
    }
    
    public static function enum( $value, $pattern ){
        return in_array($value, $pattern) ? $value : NULL;
    }
    
    public static function regex( $value, $pattern ){
        return preg_match($pattern, $value) ? $value : NULL;
    }
    
    public static function utf8( $value ){
        return UTF8::to( $value );
    }
    
    public static function safe( $value ){
        $unsafe = array('<', '>', '"', "'", '#', '&', '%', '{', '(');
        if( is_array( $value ) ){
            foreach( $value as $k=>$v ){
                $value[ $k ] = self::safe( $v );
                if( $value[ $k ] === NULL ) unset( $value[ $k ] );
            }
            return $value;
        } else {
            $value = str_replace($unsafe, '', strval($value));
        }
        
        // set to default value if there is nothing left after filtering
        return $value !== '' ? $value : NULL;
    }
    
    public static function against($value, $filter ) {
        if( is_object( $value ) ) return $value;
        $options = NULL;
        if ( is_array($filter) ) {
            $key = key($filter);
            switch($key ) {
            case 'regex':
            case 'enum':
                return call_user_func( array( __CLASS__, $key ), $value, $filter[ $key ] );
            default:
                return filter_var( $value, $key, $filter[ $key ] );
            }
        }
        if( ! method_exists( __CLASS__, $filter ) ){
            return filter_var( $value, $filter );
        }
        return call_user_func( array( __CLASS__, $filter ), $value );
    }
}
