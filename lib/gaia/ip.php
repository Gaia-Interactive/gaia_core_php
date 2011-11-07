<?php
namespace Gaia;

class IP {

    static $client;
    static $server;
    
    // use this info as informational. not safe to use as identity check for securtiy purposes.
    // client could be spoofing http headers or using a proxy that does.
    public static function client( $refresh = FALSE ) {
        if( ! $refresh && isset( self::$client ) ) return self::$client;

        //fill the array with candidates IP from various resources
        $ips = array();
        foreach( array(
            'HTTP_FORWARDED_FOR', 
            'HTTP_FORWARDED',
            'HTTP_X_FORWARDED_FOR', 
            'HTTP_X_FORWARDED',
            'HTTP_PRAGMA', 
            'HTTP_XONNECTION', 
            'HTTP_CACHE_INFO', 
            'HTTP_XPROXY', 
            'HTTP_PROXY', 
            'HTTP_PROXY_CONNECTION', 
            'HTTP_VIA', 
            'HTTP_X_COMING_FROM', 
            'HTTP_COMING_FROM',  
            'ZHTTP_CACHE_CONTROL', 
            'REMOTE_ADDR', 
            'HTTP_CLIENT_IP') as $header ){
            if( ! isset( $_SERVER[ $header ] ) ) continue;
            foreach( explode(',', $_SERVER[ $header ]) as $ip ){
                $ip = trim( $ip );
                if( strlen( $ip ) < 1 ) continue;
                if( ! @ip2long( $ip ) ) continue;
                $ips[] = $ip;
            }
            if( $ips ) break;
        }

        
        //if all resources are exhausted and not found, return false.
        return self::$client = self::extractIPFromList( $ips ); 
    }
    
    public static function isInternal( $ip ){
        if( $ip == '127.0.0.1' ) return TRUE;
        return preg_match('#^(10\.[0-9]{1,3}|172\.(3[01]|2[0-9]|1[6-9])|192\.168)\.[0-9]{1,3}\.[0-9]{1,3}$#', $ip);
    }

    public static function server($refresh = FALSE ){
        if( ! $refresh && isset( self::$server ) ) return self::$server;
        if( isset( $_SERVER['SERVER_ADDR'] ) ) return self::$server = $_SERVER['SERVER_ADDR'];
        $ip = gethostbyname(gethostname());
        if( $ip === FALSE ) $ip = '0.0.0.0';
        return self::$server = $ip;
    }
    
    
    protected static function extractIPFromList( array $ips ){
        $result = $fallback = FALSE;
        foreach ($ips as $ip) { 
            // matches all the private ip.
            #  ^(10\.[0-9]{1,3}|172\.(3[01]|2[0-9]|1[6-9])|192\.168)\.[0-9]{1,3}\.[0-9]{1,3}$
            #  /^10\.|^127\.|^172\.(?:1[6-9])\.|^224\.|^240\.|^192\.168\./
            if( self::isInternal( $ip ) ){
                if( ! $fallback ) $fallback = $ip;
            } else { 
                $result = $ip;
                break;
            }
        }
        
        //if fallback is not found it will be false
        if ($result===false) $result=$fallback;
        
        if( $result === false ) $result = '0.0.0.0';
        
        return $result;
    }

}