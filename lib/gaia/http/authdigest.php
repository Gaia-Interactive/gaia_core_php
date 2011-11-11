<?php
namespace Gaia\Http;
use Gaia\Container;
use Gaia\Store;


/*

// to do authentication.
$dba = new Store\DBA('/path/to/file');
$auth = new AuthDigest($dba);
if( ! $auth->authenticate() ){
    $headers = $auth->challenge();
    foreach($headers as $header ) header( $header );
    die("please authenticate");
}

*/


/*

// to store a password.
$dba = new Store\DBA('/path/to/file');
$auth = new AuthDigest($dba);
$ttl = time() + ( 86400 * 7 ); // password only sticks around for 1 week.
$auth->storePassword( $username, $password, $ttl );
or ...
$auth->storePassword( $username, $auth->hashPassword($password), $ttl );

*/


class AuthDigest {
    
    protected $realm = '';
    protected $domain = '';
    
    
    public function __construct( $realm = 'Restricted area',   $domain = '/' ){
        $this->realm = $realm;
        $this->domain = $domain;
    }
    
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
    
    public function challenge(){
        $headers = array();
        $headers[] = ('HTTP/1.1 401 Unauthorized');
        $headers[] = ('WWW-Authenticate: Digest realm="'.$this->realm.
                      '",qop="auth",algorithm=MD5,domain="' . $this->domain . 
                      '",nonce="'.uniqid() .
                      '",opaque="'.md5($this->realm).'"');
        return $headers;
    }
    
    public function hashPassword( $username, $password ){
        return md5( $username . ':' . $this->realm . ':' . $password );
    }
    
    protected function isMD5( $txt ){
        return preg_match('#^[a-f0-9]{32}$#', $txt);
    }
}