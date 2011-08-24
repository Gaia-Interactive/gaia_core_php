<?php

/**
 * Nonces are used to generate one-time-use tokens on the site.  They can
 * be used to prevent replay attacks, duplicate submissions, and more.
 */

namespace Gaia;

class Nonce {

    const INTERVAL = 3600;
    protected static $charset;
    protected $chunk_length = 40;
    protected $secret = '';
    
    public function __construct( $secret, $chunk_length = 40 ){
        $this->secret = $secret;
        $this->chunk_length = $chunk_length;
    }
    
    public function create($token, $expires = null) {
        if ($expires === NULL) $expires = time() + self::INTERVAL;
        $rand = self::rand($this->chunk_length);
        $nonce =  substr(sha1( $rand . $expires ), 0, $this->chunk_length);
        $digest = substr(sha1( $rand . $token . $this->secret . $expires ), 0, $this->chunk_length);
        return $nonce . $digest . $rand . $expires;        
    }

    public function check($hash, $token ) {
        if( ! is_string( $hash ) ) return FALSE;
        if( strlen( $hash ) < ($this->chunk_length * 3) + 1 ) return FALSE;
        $nonce = substr($hash, 0, $this->chunk_length);
        $digest = substr($hash, $this->chunk_length, $this->chunk_length);
        $rand = substr($hash, $this->chunk_length * 2, $this->chunk_length);
        $expires = substr($hash, $this->chunk_length * 3);
        if( ! is_numeric( $expires ) ) return false;
        if( $expires < time() ) return FALSE;
        if( substr( sha1( $rand . $expires ), 0, $this->chunk_length) != $nonce ) return FALSE;
        if( substr( sha1( $rand . $token . $this->secret . $expires ), 0, $this->chunk_length) != $digest ) return FALSE;
        return TRUE;
    }
    
    protected static function rand($length = 10 ){
        $rand = '';
        $charset = self::charset();
        $charset_len = strlen( $charset ) - 1;
        for ($i=0; $i<$length; $i++) $rand .= $charset[(mt_rand(0,$charset_len))];
        return $rand;
    }
    
    protected static function charset(){
        if( isset( self::$charset ) ) return self::$charset;
        return self::$charset =  
                    implode('', range('a', 'z')) . 
                    implode('', range('a', 'z')) .
                    implode('', range('0', '9')) ;
    }
}