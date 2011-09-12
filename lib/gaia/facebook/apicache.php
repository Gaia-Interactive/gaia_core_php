<?php
namespace Gaia\Facebook;
use Gaia\Cache;

class APICache {

    protected $facebook;
    protected $cache;
    
    public static $VERSION = 1;
    
    public static $SHORT_CACHE_TTL = 60;
    
    public static $LONG_CACHE_TTL = 3600;
    
    public static $API_CONN_TIMEOUT = 15;
    public static $API_CONN_RETRY_TIMEOUT = 2;

    public static $API_TIMEOUT = 30;
    public static $API_RETRY_TIMEOUT = 15;
    
    public static $RETRIES = 3;
    
    public function __construct( \BaseFacebook $facebook, Cache\Iface $cache ){
        $this->facebook = $facebook;
        $this->cache = $cache;
    }
    
    public function __call( $method, $args ){
        return call_user_func_array( array( $this->facebook, $method ), $args );
    }
    
    public function __get( $key ){
        return $this->facebook->$key;
    }
    
    public function __set( $key, $value ){
        return $this->facebook->$key = $value;
    }
    
    public function __isset( $key ){
        return isset( $this->facebook->$key );
    }
    
    public function __unset( $key ){
        unset( $this->facebook->$key );
    }
    
    public function api($url, $method = NULL, $params = array()) {
    
        if( is_array( $url ) ) return $this->facebook->api( $url, $method, $params );
        
        if( is_array( $method ) && empty($params) ) {
            $params = $method;
            $method = 'GET';
        }
        
        if( $method == 'POST' ) return $this->facebook->api( $url, $method, $params );

        // use namespaced cache object
        $cache = $this->cache;
        $is_cached = FALSE;

        // copy the params so we can modify it slightly to get a more reusable cache key
        $_cacheparams = $params;

        // if this is personal data, we need the fb id or access token in the cache key checksum
        if( substr( $url, 0, 3) == '/me' ){
            $uid = $this->facebook->getUser();
            $_cacheparams['access_token'] = (  $uid ) ? $uid : $this->facebook->getAccessToken();
        }

        // if it is the /me url, lower the soft timeout so we can be more accurate.
        $soft_timeout = ( $url == '/me' ) ? self::$SHORT_CACHE_TTL : self::$LONG_CACHE_TTL;

        // create a cache key based off of the url and params
        $cacheKey = 'graph/' . self::$VERSION . '/'. md5($url . serialize($_cacheparams));

        // grab the data from the cache
        $data = $cache->get($cacheKey);

        // did we get back a valid response?
        if (is_array($data) && is_array( $data['response'] ) ) {
            // mark a flag for later that let's us know we have valid data
            $is_cached = TRUE;

            // check to see if the soft timeout has expired.
            // if not, return the data.
            if( $data['touch'] + $soft_timeout > self::time() ){
                return $data['response'];
            }

            // make sure no one else is trying to refresh this data too.
            if( ! $cache->add($cacheKey . '.lock', 1, 30) && $cache->get($cacheKey . '.lock')){
                return $data['response'];
            }
        }
        // placeholder for exception.
        $e = NULL;

        // keep track of when we started. don't want to try forever.
        $start = time();

        // override facebook curl opts temporarily, but hold on to what they were
        // so we can restore them when we are done.
        $orig_opts = \BaseFacebook::$CURL_OPTS;
         \BaseFacebook::$CURL_OPTS[ CURLOPT_CONNECTTIMEOUT] = $is_cached ? self::$API_CONN_RETRY_TIMEOUT : self::$API_CONN_TIMEOUT;
         \BaseFacebook::$CURL_OPTS[ CURLOPT_TIMEOUT ] = $is_cached ? self::$API_RETRY_TIMEOUT : self::$API_TIMEOUT;
        $tries = $is_cached ? 1 : self::$RETRIES;
        // try to get data from facebook. loop a few times.
        for( $i = 0; $i < $tries; $i++){
            try {
                // ask facebook for my friends
                // need to limit how long we try.
                $data = array('response'=> $this->facebook->api($url, $params), 'touch'=>self::time());

                // did we get a response back? if not, keep trying!
                if( ! is_array( $data['response'] ) ) continue;

                // yay! we got data back.
                // restore facebook curl opts to what they were.
                 \BaseFacebook::$CURL_OPTS = $orig_opts;

                $cache->set($cacheKey, $data, 86400 * 7); // set the data into the cache for a week.
                // return the data from the array.
                return $data['response'];
            } catch (Exception $e){

                if ($e instanceof FacebookApiException && $e->getType() != 'CurlException' ) {
                    $cache->delete($cacheKey);
                    $is_cached = FALSE;
                    break;
                }
                // don't do anything yet. do some retry looping.
            }

            // did we run out of time?
            // if so, bail.
            if( ( time() - $start ) > self::$API_RETRY_TIMEOUT ) {
                if( ! $e instanceof Exception ) $e = new Exception('took too long to fetch '.$url.' from Facebook.');
                break;
            }

            // still trying ... loop again!.
        }

        // restore facebook curl opts to what they were.
         \BaseFacebook::$CURL_OPTS = $orig_opts;

        // do we have a slightly stale version from the cache? if so use that instead.
        if ( $is_cached ) {

            // update the touch in the cache so we don't keep trying to refresh.
            // try again in 5 minutes.
            $data['touch'] += 300;
            $cache->set($cacheKey, $data, 86400 * 7);

            // return the data;
            return $data['response'];
        }
        // looks like we looped and looped and got nothing back.
        // might be because of an exception from earlier. if so, throw that.
        if( $e instanceof Exception ) throw $e;

        // no exception thrown, but no data either. DOH!
        throw new Exception('retries exceeded');
    }
    
    protected static function time(){
        return time();
    }

}