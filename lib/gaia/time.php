<?php
namespace Gaia;

class Time {
    protected static $offset = 0;
    
    public static function now(){
        return time() + self::$offset;
    }
    
    public static function offset( $v = 0 ){
        return self::$offset += $v;
    }
}