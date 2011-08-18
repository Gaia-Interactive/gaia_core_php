<?php

/**
 * nonce.php
 * Nonces are used to generate one-time-use tokens on the site.  They can
 * be used to prevent replay attacks, duplicate submissions, and more.
 * @author Jakobo <rjheuser@gaiaonline.com>
 * @package GAIAONLINE
 * @license private/license/UNKNOWN.txt
 * @copyright 2003-present GAIA Interactive, Inc.
 * @version    $LastChangedRevision$
 */
 namespace Gaia;

class Nonce {

    static $cacher = NULL;
    const DEFAULT_TIME_INTERVAL = 3600;
    const DISABLED = FALSE;
    
    static $disable_expired = false;

    /**
     * Disables the expired check for validation
     **/
    static public function disableExpiredCheck() {
        self::$disable_expired = true;
    }

    /**
     * generateNonce(user_id, timestamp) generate a Nonce set
     * uses the user ID the time, and the secret (not provided)
     * @param int $userId the user ID to create a nonce for (default is 0, force userdata)
     * @param int $timestamp the timestamp to use (default is -1, force siteconfig)
     * @return array a 3 item array of (nonce, created, digest) to be placed in the form
     * @static
     */
    static public function generate($id_token, $timestamp = null) {

        if ($timestamp === null) {
            $timestamp = currenttime();
        }

        $nonce = abs_crc32_64bit($id_token . "," . $timestamp);
        $digest = abs_crc32_64bit($nonce . "," . $timestamp . "," . Nonce::_secret());

        $returnArray = array("nonce" => $nonce, "created" => $timestamp, "digest" => $digest);

        return $returnArray;

    }

    /**
     * validate(nonce, created, digest) Validate a nonce by checking its values
     * This function works through the nonce process in order to assert that
     * the nonce information passed in is valid.  The application logic should handle
     * nonce preservation if required (for example, storing in post history)
     * @static
     */
    static public function validate($nonce, $created, $digest, $id_token) {

        if (self::DISABLED) {
            // nonce disabled
            return TRUE;
        }

        if ($nonce == "") { return false; }
        if (($created < 0) || (!FunctionLibrary()->is_number($created))) { return false; }
        if ($digest == "") { return false; }
        
        // generate a nonce using the good 'ole process
        $nonceData = Nonce::generate($id_token, $created);

        // verify nonce is a valid nonce
        if ($nonce != $nonceData["nonce"]) { return false; }

        // verify digest
        $checkDigest = abs_crc32_64bit($nonce . "," . $created . "," . Nonce::_secret());
        if ($checkDigest != $digest) { return false; }

        // nonce is valid, digest is valid.  All clear
        return true;
    }

   /**
    * generate the nonce as a string
    * @see Nonce::generate();
    */
    static public function generateString( $id_token, $timestamp = null ) {
        return implode('.', array_values(Nonce::generate($id_token, $timestamp) ) );
    }

   /**
    * validate a nonce string
    * @see Nonce::validate()
    */
    static public function validateString( $string, $id_token ) {
        $nonceData = self::parseNonceString($string);
        if (!$nonceData) {
            return FALSE;
        }
        list( $nonce, $created, $digest ) = $nonceData;
        return Nonce::checkExpired($created) && Nonce::validate($nonce, $created, $digest, $id_token );
    }

    static public function parseNonceString($string){
        if( ! preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $string ) ) return FALSE;
        return explode( '.', $string );
    }

    /**
     * using nonce in memcache to prevent reply attack. return TRUE when
     * the $nonce is ok to use, FALSE otherwise
     * @return bool
     */
    static public function checkReplayAttack($nonce, $time_interval = self::DEFAULT_TIME_INTERVAL){
        if ( !self::parseNonceString($nonce)) { return FALSE; }
        return self::checkCacheKey($nonce, $time_interval);
    }

    static public function checkFlood($scope, $flood_interval = NULL /*use class flood_interval*/,  $user_id = NULL/*use userdata.user_id*/){
        $scope = self::sanitizeScope($scope);

        if (!$user_id){
            $user_id= userdata::get('user_id');
        }

        if (!$flood_interval){
            $flood_interval = self::DEFAULT_TIME_INTERVAL;
        }

        $key = strtolower($scope) . '.' . $user_id;
        return self::checkCacheKey($key, $flood_interval);
    }
    
    static protected function sanitizeScope( $scope ){
        if( ! is_scalar( $scope ) ) $scope = md5( serialize( $scope ) );
        $scope = strtolower($scope);
        return $scope;
    }

    static protected function checkCacheKey($key, $time_interval){
        $ret = self::cacher()->add($key, 1 /*unused value*/, NULL /*no compress*/, $time_interval);
        if ($ret){
            // we successfully added $nonce to memcache, the key does not
            // already exist
            return TRUE;
        } else {
            // add() falied. let's try to use get() to test whether memcached
            // is down or the key already exists
            $ret = self::cacher()->get($key);
            if ($ret !== FALSE){
                // memcached is not down because we are able to read the key,
                // thus confirming the key already exists.
                return FALSE;
            } else {
                // add() and get() both failed. We are having trouble
                // connecting to memcached. In this case we would let the
                // action passes because we cannot check it effectively.
                return TRUE;
            }
        }
    }

    /**
     * check whether the nonce has expired. Return TRUE if the nonce is still
     * good, FALSE if nonce is expired
     * @ return bool
     */
    static public function checkExpired($nonce_created, $time_interval = self::DEFAULT_TIME_INTERVAL){
        if (self::$disable_expired == true) {
            return true;
            self::$disable_expired = false;
        }
        //print "current time: ".currenttime()."<P>";
        //print "noncecreated: ".($nonce_created+$time_interval)."<P>";

        return ($nonce_created + $time_interval) > currenttime();
    }

    /**
     * return memecahe namespaced for nonce
     * @return NamespacedMemcache
     */
    static protected function cacher(){
        if (self::$cacher instanceof NamespacedMemcache) return self::$cacher;
        return self::$cacher = new NamespacedMemcache(memcache(), 'NONCE_');
    }


   /**
    * A secret string value used to salt strings for the NONCE.
    * @return string
    * @access protected
    * @static
    */
    static protected function _secret()
    {
        return include "/etc/gaia/nonce.secret.php';
    }

}