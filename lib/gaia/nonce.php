<?php

/**
 * Nonces are used to generate one-time-use tokens on the site.  They can
 * be used to prevent replay attacks, duplicate submissions, and more.
 */

namespace Gaia;

class Nonce {

    const INTERVAL = 3600;
    protected $digest_length = 40;
    protected $secret = '';
    
    public function __construct( $secret, $digest_length = 40 ){
        $this->secret = $secret;
        $this->digest_length = $digest_length;
    }
    
    public function create($token, $expires = null) {
        if ($expires === NULL) $expires = time() + self::INTERVAL;
        return substr( sha1($token . "-" . $expires . "-" . $this->secret), 0, $this->digest_length) . $expires;
    }

    public function check($input, $token ) {
        if( ! is_string( $input ) ) return FALSE;
        if( ! preg_match('/^[a-f0-9]{' . $this->digest_length . '}([0-9]+)$/', $input, $matches ) ) return FALSE;
        if ($input != Nonce::create($token, $expires = $matches[1])) return FALSE;
        return $expires >= time() ? TRUE : FALSE;
    }
}