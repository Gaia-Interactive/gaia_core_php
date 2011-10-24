<?php
namespace Gaia\Facebook;
use Gaia\Store;

// extend the base facebook object for two reasons:
// provide persistent storage for variables not found in the signed request,
// and fix bugs related to js/php sdk integration.
class Persistence extends \BaseFacebook
{    
    // keep track of all the keys we will store. 
    protected static $persistent_keys = array('user_id', 'code', 'access_token', 'oauth_token', 'state');
    
    protected $store;
    
    // extend the constructor so we can validate the persistent data.
    function __construct( $config, Store\Iface $store = NULL ){
        $this->store = ( $store ) ? $store : new Store\KVP;
        parent::__construct( $config );
        $this->validatePersistentData();
    }
    
    // make sure the persistent data doesn't conflict with variables in signed request.
    // if it does, we need to clear out all the variables in persistent data and start over.
    public function validatePersistentData(){
    
        // grab the data stored in signed request.
        // this can come from URL querystring parameters, or from the cookie
        // written by the js-sdk.
        $data = $this->getSignedRequest();
        
        // it returns false if nothing was found, otherwise we get an array.
        if( ! $data ) return;
        
        
        // now loop through all the persistent keys and check them against signed request data.
        foreach( self::$persistent_keys as $key ){
            // if we don't have this key in signed request, skip to the next.
            if( ! isset( $data[ $key ] ) ) continue;
            
            // grab the data out of persistent storage.
            $value = $this->getPersistentData( $key );
            
            // if we didn't find any data, skip
            if( ! $value ) continue;
            
            // if the signed request data matches what we have in persistent storage, we are fine.
            if( $data[ $key ] == $value ) continue;
            
            // if we got this far, we have a mis-match. clear out all the variables in persistent 
            // storage. they are all suspect.
            $this->clearAllPersistentData();
            
            // no need to continue looping, since we got rid of all the data in persistent storage.
            break;
        }
    }
    
    // override behavior of the BaseFacebook class. the logic is botched in here.
    // it won't clean up a wedged signed_request cookie. this one will.
    protected function throwAPIException($result) {
        $e = new \FacebookApiException($result);
        switch ($e->getType()) {
          // OAuth 2.0 Draft 00 style
          case 'OAuthException':
            // OAuth 2.0 Draft 10 style
          case 'invalid_token':
            $this->setAccessToken(null);
            $this->user = 0;
            $this->clearCookies();
        }
        throw $e;
    }
    
    // get rid of persistent data and signed request cookies.
    public function clearCookies(){
        $this->clearSignedRequest();
        $this->clearAllPersistentData();
    }
    
    // get rid of the signed request cookie.
    public function clearSignedRequest(){
        $key = $this->getSignedRequestCookieName();
        if( ! isset( $_COOKIE[ $key ] ) ) return;
        unset( $_COOKIE[ $key ] );
        setcookie( $key, '', time() - (86400 * 365), '/');
    }
    
    // write persistent data into a signed cookie.
    protected function setPersistentData($k, $v) {
        $k = $this->persistentVariableName( $k );
        if( ! $k ) return;
        $res = ($v === NULL) ? $this->store->delete( $k ) : $this->store->set( $k, $v );
        var_dump( $this->store );
    
    }
    
    // get persistent data out of the cookie.
    protected function getPersistentData($k, $default = false) {
        $k = $this->persistentVariableName( $k );
        if( ! $k ) return;
        return $this->store->get( $k );
    }
    
    // remove a particular persistent data key
    protected function clearPersistentData($k) { 
        $k = $this->persistentVariableName( $k );
        if( ! $k ) return;
        $this->store->delete( $k );
    }
    
    // get rid of all the persistent data.
    protected function clearAllPersistentData() { 
        foreach( self::$persistent_keys as $key ){
            $this->clearPersistentData( $key );
        }
    }
    
    // get all the persistent data.
    public function getAllPersistentData() { 
        $data = array();
        foreach( self::$persistent_keys as $key ){
            $value = $this->getPersistentData( $key, NULL );
            if( $value === NULL ) continue;
            $data[ $key ] = $value;
        }
        return $data;
    }

    // get the name of the persistent variable. prefix the same way as other keys.
    protected function persistentVariableName($key) {
        return $key;
        if( ! in_array($key, self::$persistent_keys) ) return FALSE;
        return 'fbsr_' . $this->getAppId() . $key;
    }
}
