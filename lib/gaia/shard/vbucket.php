<?php
namespace Gaia\Shard;
// @see: https://github.com/gaiaops/gaia_core_php/wiki/shard-vbucket
class VBucket {

    protected $map = array();
    const COUNT = 1000;
    
    // either pass in an array of frabment => shard  key value pairs.
    // array(0=>1, ... )
    // '|0-1:| ...'
    public function __construct( array $shards = NULL ){
        if( ! $shards ) return;
        $ct = count( $shards );
        for( $vbucket = 0; $vbucket < self::COUNT; $vbucket++){
            $this->map[ $vbucket ] = $shards[ floor( $vbucket / (self::COUNT / $ct )) ];
        }
    }
    
    public function shard($id){        
        $vbucket = self::hash($id);
        return ( isset( $this->map[ $vbucket ] ) ) ? $this->map[ $vbucket ] : NULL;
    }
    
    
    public function shards(){        
        return array_unique( $this->map );
    }
    
    public function export(){
        return $this->map;
    }
    
    // make sure we generate a consistent hash whether on 64bit or 32bit machine.
    public static function hash($str){
        $crc = abs(crc32($str));
        if( $crc & 0x80000000){
            $crc ^= 0xffffffff;
            $crc += 1;
        }
        return $crc % self::COUNT;
    }
    
}
