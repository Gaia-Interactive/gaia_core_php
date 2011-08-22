<?php

/**
 * Nonces are used to generate one-time-use tokens on the site.  They can
 * be used to prevent replay attacks, duplicate submissions, and more.
 */

namespace Gaia;

class Nonce {

    const INTERVAL = 3600;
    protected static $digest_length = 40;
    protected static $secret = '';
    
    public static function create($token, $expires = null) {
        if ($expires === NULL) $expires = time() + self::INTERVAL;
        return substr( sha1($token . "-" . $expires . "-" . Nonce::secret()), 0, self::$digest_length) . $expires;
    }

    public static function check($input, $token ) {
        if( ! is_string( $input ) ) return FALSE;
        if( ! preg_match('/^[a-f0-9]{' . self::$digest_length . '}([0-9]+)$/', $input, $matches ) ) return FALSE;
        if ($input != Nonce::create($token, $expires = $matches[1])) return FALSE;
        return $expires >= time() ? TRUE : FALSE;
    }
    
    public static function secret(){
        if( self::$secret ) return self::$secret;
        $file =  __DIR__ . '/.nonce.secret.php';
        if( ! file_exists( $file ) ) throw new Exception('nonce-secret undefined', $file);
        $res = include( $file );
        if( ! is_string( $res ) || strlen( $res ) < 10 )  throw new Exception('nonce-secret invalid', $res);
        return self::$secret = $res;
    }
    
    public static function setDigestLength( $v ){
        if( $v > 0 && $v < 40 ) return self::$digest_length = $v;
        return self::$digest_length;
    }
    
    public static function setSecret( $v ){
        return self::$secret = $v;
    }
}