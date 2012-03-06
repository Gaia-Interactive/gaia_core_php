<?php
namespace Gaia\APN;
use Gaia\Time;
use Gaia\Exception;

class Queue {

    const SEP = '--';
    
    public static function store( AppNotice $notice ){
        $binary_notification = $notice->serialize();
        $config = Config::instance();
        $tube = Config::instance()->queuePrefix() . self::SEP . $notice->getApp() . self::SEP . date('Ymd', Time::now() );
        $try= $config->retries() + 1;
        $conns = $config->connections();
        $keys = array_keys( $conns );
        shuffle( $keys ); 
        $ttr = 30; // $config->ttr();
        $priority = 0; //floor( $notice->getExpires() - time::now() / 3600 );
        $delay = 0;
        
        foreach( $keys as $key ){
            $conn = $conns[ $key ];
            if( ! $try-- ) break;            
            $res = $conn->putInTube( $tube, $binary_notification, $priority, $delay, $ttr );
            if( ! $res ) {
                continue;
            }
            return $conn->hostInfo() . '-' . $res;
        }
        throw new Exception('storage error', $conns);
    }
    
    public static function find( $key ){
        if( ! is_string( $key ) || strpos($key, '-') === FALSE ) throw new Exception('invalid id', $key );
        list( $server, $id ) = explode('-', $key, 2);
        if( ! $server ) throw new Exception('invalid id', $key );
        $conns = Config::instance()->connections();
        if( ! isset( $conns[ $server ] ) ) throw new Exception('server not found', $key );
        $conn = $conns[ $server ];
        $res = $conn->peek( $id );
        if( ! $res ) throw new Exception('conn error', $conn );
        return new AppNotice( $res->getData() );
    }
    
    public static function remove($id){
        list( $server, $id ) = explode('-', $id, 2);
        if( ! $server ) return FALSE;
        $conns = Config::instance()->connections();
        if( ! isset( $conns[ $server ] ) ) return false;
        $conn = $conns[ $server ];
        $res = $conn->delete( new \Pheanstalk_Job($id, '') );
        if( ! $res ) throw new Exception('conn error', $conn );
        return $res;
    }
    
    public static function fail($id){
        list( $server, $id ) = explode('-', $id, 2);
        if( ! $server ) return FALSE;
        $conns = Config::instance()->connections();
        if( ! isset( $conns[ $server ] ) ) return false;
        $conn = $conns[ $server ];
        $res = $conn->release( new \Pheanstalk_Job($id, ''), /* priority */ 1000, /* ttr */ 600  );
        if( ! $res ) throw new Exception('conn error', $conn );
        return $res;
    }
}
