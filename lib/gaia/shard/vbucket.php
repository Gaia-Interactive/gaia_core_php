<?php
namespace Gaia\Shard;

class VBucket {

    protected $map = array();
    const FRAG_SIZE = 1000;
    
    // either pass in an array of frabment => shard  key value pairs.
    // array(0=>1, ... )
    // '|0-1:| ...'
    public function __construct( array $shards = NULL ){
        if( ! $shards ) return;
        $ct = count( $shards );
        for( $vbucket = 0; $vbucket < self::FRAG_SIZE; $vbucket++){
            $this->map[ $vbucket ] = $shards[ floor( $vbucket / (self::FRAG_SIZE / $ct )) ];
        }
    }
    
    public function shard($id){        
        $vbucket = self::hash($id) % self::FRAG_SIZE;
        if( isset( $this->map[ $vbucket ] ) ) return $this->map[ $vbucket ];
        return NULL;
    }
    
    public function shards(){        
        return array_unique( $this->map );
    }
    
    public function export(){
        $this->map;
    }
    
    // make sure we generate a consistent hash whether on 64bit or 32bit machine.
    protected static function hash($str){
        $crc = abs(crc32($str));
        if( $crc & 0x80000000){
            $crc ^= 0xffffffff;
            $crc += 1;
        }
        return $crc;
    }
    
}
