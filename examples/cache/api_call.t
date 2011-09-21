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
        $ch = curl_init( 'http://maps.googleapis.com/maps/api/directions/json?' . $qs );
        if( ! empty( $opts ) ) curl_setopt_array($ch, $opts );
        $headers = array(
                    'Connection: Keep-Alive',
                    'Keep-Alive: 300',
                    'Accept-Charset: ISO-8859-1,utf-8',
                    'Accept-language: en-us',
                    'Accept: text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*',
                    'application/x-www-form-urlencoded',
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        $curl_data = curl_exec($ch);
        $curl_info = curl_getinfo($ch);
        if( ! is_array( $curl_info ) ) $curl_info = array();
        if( ! isset( $curl_info['http_code'] ) ) $curl_info['http_code'] = 0;
        if( ! isset( $curl_info['header_size'] ) ) $curl_info['header_size'] = 0;
        $response_header =  substr( $curl_data, 0, $curl_info['header_size'] );
        $header_lines = explode("\r\n", $response_header);
        $headers = array();
        foreach( $header_lines as $line ){
            if( ! strpos( $line, ':') ) continue;
            list( $k, $v ) = explode(':', $line );
            trim( $k );
            trim( $v );
            $headers[ $k ] = $v;
        }
        $body = substr( $curl_data, $curl_info['header_size']);
        $curl_info['headers'] = new \Gaia\Container($headers);
        $curl_info['response_header'] = $response_header;
        $curl_info['body'] = $body;
        if( $curl_info['http_code'] != 200 ) throw new Exception('curl error', $curl_info );
        $response = json_decode( $body, TRUE);
        if( ! is_array( $response ) ) throw new Exception('curl error', $curl_info );
        return $response;
    }

    protected static function cache(){
        return new Cache\Prefix( new Cache\Mock, __CLASS__ . '/' );
    }
    
    protected static function time(){
        return time() + self::$time_offset;
    }
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




