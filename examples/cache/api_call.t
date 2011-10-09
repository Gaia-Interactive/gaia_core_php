#!/usr/bin/env php
<?php

namespace Demo;
use Gaia\Cache;
use Gaia\Exception;
use Gaia\Test\Tap;

include __DIR__ . '/../common.php';
include __DIR__ . '/connection.php';


//  http://maps.googleapis.com/maps/api/directions/json?origin=Chicago,IL&destination=Los+Angeles,CA&waypoints=Joplin,MO|Oklahoma+City,OK&sensor=false

class GoogleMaps {
    // only caching for a short time for demo purposes, so you can see it refresh.
    const CACHE_TIMEOUT = 10;
    
    public static $time_offset = 0;
    
    public static function getDirections( $origin, $destination, $waypoints = NULL ){
        $qs = http_build_query(array('origin'=>$origin, 'destination'=>$destination, 'waypoints'=>$waypoints, 'sensor'=>'false'));
        $cachekey = md5($qs);
        $cache = self::cache();
        $data = $cache->get( $cachekey );
        $refresh = FALSE;
        if( ! is_array( $data ) || ! isset( $data['response'] ) || ! isset( $data['cachetime'] )  ){
            $data = array('response'=>self::query( $qs, array(CURLOPT_CONNECTTIMEOUT=>5, CURLOPT_TIMEOUT=>30) ), 'cachetime'=>self::time() );
            $cache->set($cachekey, $data, 86400);
        } elseif( $data['cachetime'] + self::CACHE_TIMEOUT < self::time() ){
            try {
                $res = array('response'=>self::query( $qs, array(CURLOPT_CONNECTTIMEOUT=>1, CURLOPT_TIMEOUT=>1)  ), 'cachetime'=>self::time() );
            } catch( Exception $e ){
                $data['cachetime'] = self::time();
            }
            $cache->set($cachekey, $data, 86400);
        }
        return $data['response'];
    }
    
    protected static function query( $qs, array $opts = array()){
        $http = new \Gaia\HTTP\Request( 'http://maps.googleapis.com/maps/api/directions/json?' . $qs );
        $response = $http->exec( $opts );
        if( $response->http_code !== 200 ) throw new Exception('curl error', $response );
        if( ! $response->body ) throw new Exception('curl error', $response );
        $data = json_decode($response->body, TRUE);
        if( ! is_array( $data ) ) throw new Exception('curl error', $response );
        return $data;
    }

    protected static function cache(){
        return new Cache\Prefix( new Cache\Mock, __CLASS__ . '/' );
    }
    
    protected static function time(){
        return time() + self::$time_offset;
    }
}

if( ! function_exists('curl_init') ){
    Tap::plan('skip_all', 'php curl library not installed');
}

if( ! @fsockopen('maps.googleapis.com', 80) ){
    Tap::plan('skip_all', 'could not connect to google api');
}

Tap::plan(3);
$start = microtime(TRUE);
$directions = GoogleMaps::getDirections('Chicago, IL', 'Los Angeles, CA');
$elapsed1 = number_format(microtime(TRUE) - $start, 5);


$start = microtime(TRUE);
$directions = GoogleMaps::getDirections('Chicago, IL', 'Los Angeles, CA');
$elapsed2 = number_format(microtime(TRUE) - $start, 5);



$info = array();
foreach( $directions['routes'][0]['legs'][0]['steps'] as $k => $v ){
    $info[ $k ] = array('instructions'=>$v['html_instructions'], 'distance'=>$v['distance']['text'], 'duration'=>$v['duration']['text']);
}

Tap::debug( $info );

Tap::cmp_ok( $elapsed2, '<', 3, "cached directions took less than a 3 secs to retrieve: $elapsed2 s");
Tap::cmp_ok( $elapsed2, '<', $elapsed1, "cached directions took less than the original call: $elapsed1");

GoogleMaps::$time_offset += GoogleMaps::CACHE_TIMEOUT + 1;

$start = microtime(TRUE);
$directions = GoogleMaps::getDirections('Chicago, IL', 'Los Angeles, CA');
$elapsed3 = number_format(microtime(TRUE) - $start, 5);

Tap::cmp_ok( $elapsed2, '<', $elapsed3, "refreshing took longer than cache read: $elapsed3");




