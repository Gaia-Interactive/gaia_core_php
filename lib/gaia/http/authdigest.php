<?php
namespace Gaia\Http;
use Gaia\Container;
use Gaia\Store;

// a class to do digest http authentication in php.
class AuthDigest {
    
    protected $realm = '';
    protected $domain = '';
    
    /**
    * optionally set the realm and domain.
    * if unsure, leave defaults.
    */
    public function __construct( $realm = 'Restricted area',   $domain = '/' ){
        $this->realm = $realm;
        $this->domain = $domain;
    }
    
    /*
    * pass in a storage object that can retrieve passwords by username.
    * if the returned password is a hex string of 32 characters, we assume it is a hashed password.
    * otherwise we assume you stored it in clear text and we hash it for you.
    * depends on your security needs, but hashing the passwords in advance is avised.
    * see the hashPassword method.
    */
    public function authenticate( Store\Iface $storage ){
        $digest = isset( $_SERVER['PHP_AUTH_DIGEST'] ) ? $_SERVER['PHP_AUTH_DIGEST'] : '';
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : '';
        if(! preg_match('/username="([^"]+)"/', $digest, $username) || 
           ! preg_match('/nonce="([^"]+)"/', $digest, $nonce) ||
           ! preg_match('/response="([^"]+)"/', $digest, $response) ||
           ! preg_match('/opaque="([^"]+)"/', $digest, $opaque) ||
           ! preg_match('/uri="([^"]+)"/', $digest, $uri )
           ) return FALSE;
        $username = $username[1];
        $nonce = $nonce[1];
        $response = $response[1];
        $opaque = $opaque[1];
        $uri = $uri[1];
        $password = $storage->get($username);
        if(strlen( $password ) < 1 ) return FALSE;
        $A1 = ! $this->isMd5( $password ) ? $this->hashPassword( $username, $password ) : $password;
        $A2 = md5($request_method.':'.$uri);
        
        if(  preg_match('/qop="?([^,\s"]+)/', $digest, $qop) &&  
             preg_match('/nc=([^,\s"]+)/', $digest, $nc) &&  
             preg_match('/cnonce="([^"]+)"/', $digest, $cnonce) 
            ) { 
            $valid_response = md5($A1.':'.$nonce.':'.$nc[1].':'.$cnonce[1].':'.$qop[1].':'.$A2); 
         } else { 
            $valid_response = md5($A1.':'.$nonce.':'.$A2); 
        } 
        if( $response != $valid_response ) return FALSE;
        return $username;
    }
    
   /**
    * return the header needed to challenge the client.
    */
    public function challenge(){
        return 'WWW-Authenticate: Digest realm="'.$this->realm.
                '",qop="auth",algorithm=MD5,domain="' . $this->domain . 
                '",nonce="'.uniqid() .
                '",opaque="'.md5($this->realm).'"';
    }
    
    /**
    * used internally to validate the username and password agaisnt the digest.
    * but it can also be used externally to hash the password and store it as an md5 instead
    * of a clear text password.
    *
    * example:
    *
    *   $storage->set( $username, $authdigest->hashPassword($username, $password ), $ttl );
    *
    * If a hacker comes across this password in a database or elsewhere they won't be able to
    * use it to authenticate.
    */
    public function hashPassword( $username, $password ){
        return md5( $username . ':' . $this->realm . ':' . $password );
    }
    
    /**
    * utility method for determining if the password from storage is a hash or cleartext.
    */
    protected static function isMD5( $txt ){
        return preg_match('#^[a-f0-9]{32}$#', $txt);
    }
}