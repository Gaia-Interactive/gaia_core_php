<?php
namespace Gaia;

/**
 * @package GAIA
 * @author CarbonPhyber <dwortham@gaiaonline.com>
 */

/**
 * UTF8
 * @requires multibyte (mb_*) library
 */
class UTF8 {
    /**
     * @access protected
     * @static
     */
    protected static $debug = FALSE;

    /**
     * is
     * Is the $input already a valid UTF-8 string?
     * @access public
     * @static
     */
    public static function is($input) {
        if(!is_string($input)) return $input;
        return self::$debug
            ? mb_check_encoding($input, 'UTF-8')
            : @mb_check_encoding($input, 'UTF-8');
    }
    
    /**
    * Turn on the debug flag
    */
    public static function debug( $bool = TRUE ){
        self::$debug = $bool ? TRUE : FALSE;
    }

    /**
     * to
     * Convert the $input to UTF-8 only if it is not already detected to be UTF-8.
     * @access public
     * @static
     */
    public static function to($input) {
        // convert any objects to an array.
        if( is_object( $input ) ) {
            $input = (array) $input;
        }

        // arrays and objects need to be traversed
        if(is_array($input)) {
            $input = (array) $input;
            foreach( $input as $key => $value) {
                if(is_string($key)) {
                    if( !self::is($key) ) {
                        unset($input[$key]);
                        $input[self::to($key)] = $value;
                    }
                }
                $input[$key] = self::to($value);
            }
            return $input;
        }  elseif (is_string($input)) {
            // this is the whole reason for this implementation:
            // will auto-detect the $input characterset and convert it to UTF-8 (if possible)
            return self::is($input)
                    ? $input
                    : self::$debug
                        ? mb_convert_encoding($input, 'UTF-8', 'auto')
                        : @mb_convert_encoding($input, 'UTF-8', 'auto');
        // all non-string scalar types {boolean, integer, float, etc.}.
        } else {
            return $input;
        }
    }
}
