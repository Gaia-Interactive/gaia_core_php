<?php

/**
 * Nonces are used to generate one-time-use tokens on the site.  They can
 * be used to prevent replay attacks, duplicate submissions, and more.
 */

namespace Gaia;

class Nonce {
    // default number of seconds a nonce is good for.
    const INTERVAL = 3600;
    
    // seed characters for random string.
    protected static $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    
    // how many characters to use in the digest
    protected $digestlen = 40;
    
    // how many characters to use in the random string.
    protected $randlen = 9;
    
    // the private key of the nonce, known only to the application. 
    // Keep this value out of your application code.
    protected $secret = '';
    
    const expireslen = 11;
    
    /**
    * class constructor.
    * pass in the secret seed value for the nonce.
    * if the secret is leaked, the nonce is fairly useless.
    * The more random you make the secret, the better.
    * Can't specify exactly how long to make the nonce since you need 11 chars to specify a 
    * timestamp and at least 1 character for the random chunk and 1 character for the checksum.
    * in practice a number between 30 and 91 is pretty good. 91 is virtually unhackable.
    */
    public function __construct( $secret, $len = NULL ){
        $this->secret = $secret;
        if( $len != NULL ){
            if( $len < 15 ) $len = 15;
            $this->digestlen = floor( ($len - self::expireslen ) / 2 );
            if( $this->digestlen < 3 ) $this->digestlen = 3;
            if( $this->digestlen > 40 ) $this->digestlen = 40;
            $this->randlen = $len - ( $this->digestlen + self::expireslen );
            if( $this->randlen < 1 ) $this->randlen = 1;
        }
    }
    
    /**
    * create a nonce hash based on a token. A token is a publicly known variable that is consistent
    * from one request to the next. Examples of a good token are:
    *     user id
    *     session id
    *     ip address
    *     form input variable that doesn't change.
    *     ssh public key.
    *
    * The token passed here must be the same as the value passed into check.
    * in other words:  $nonce->check( $nonce->create( $token ), $token ) == TRUE;
    * The optional expires parameter specifies a unix timestamp on how long the nonce is good for.
    * give yourself some wiggle room. An hr or two.
    */
    public function create($token, $expires = null) {
        if ($expires === NULL) $expires = time() + self::INTERVAL;
        $rand = '';
        $charset_len = strlen( self::$charset ) - 1;
        for ($i=0; $i<$this->randlen; $i++) $rand .= self::$charset[(mt_rand(0,$charset_len))];        
        $digest = substr(sha1( $rand . $token . $this->secret . $expires ), 0, $this->digestlen);
        return $digest . $rand . str_pad( $expires, self::expireslen, '0', STR_PAD_LEFT);        
    }
    
    /**
    * given the hash value returned from the create method above, is the nonce valid for a given token?
    * returns a boolean, TRUE if the hash validates and isn't expired.
    */
    public function check($hash, $token ) {
        if( ! is_string( $hash ) ) return FALSE;
        $expireslen = self::expireslen;
        if( strlen( $hash ) != ($this->digestlen + $this->randlen + self::expireslen)  ) return FALSE;
        $digest = substr($hash, 0, $this->digestlen);
        $rand = substr($hash, $this->digestlen, $this->randlen);
        $expires = ltrim(substr($hash, $this->digestlen + $this->randlen), '0');
        if( ! is_numeric( $expires ) ) return FALSE;
        if( $expires < time() ) return FALSE;
        if( substr( sha1( $rand . $token . $this->secret . $expires ), 0, $this->digestlen) != $digest ) return FALSE;
        return TRUE;
    }
    
    public function __toString(){ 
        return 'object ' . get_class( $this ) . '{}'; 
    }
}