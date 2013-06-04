<?php
namespace Gaia\EnumPath;
use Gaia\Exception;

class Util {
    
 
    const SEP = '/';
    
    
    public static function separator(){
        return self::SEP;
    }
    
    
    public static function validatePath( $path ){
        if( ! preg_match('#^[0-9' . preg_quote(self::SEP, '#') . ']+?$#', $path) ) {
            throw new Exception('invalid path: ' . $path );
        }
        return TRUE;
    }
    
    public static function validateId( $id ){
        if( ! ctype_digit( strval( $id ) ) ) {
            throw new Exception('invalid id: ' . $id );
        }
        return TRUE;
    }
}