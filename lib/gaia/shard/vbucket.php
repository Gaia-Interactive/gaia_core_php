<?php
namespace Gaia\Shard;

class VBucket {

    protected $map = array();
    const FRAG_SIZE = 1000;
    
    // either pass in an array of frabment => shard  key value pairs.
    // array(0=>1, ... )
    // '|0-1:| ...'
    public function __construct( array $shards = NULL ){
        if( $shards ) $this->populate( $shards );
    }
    
    public function populate( array $shards ){
        $ct = count( $shards );
        if( $ct == self::FRAG_SIZE ){
            return $this->map = $shards;
        }
        for( $vbucket = 0; $vbucket < self::FRAG_SIZE; $vbucket++){
            $this->map[ $vbucket ] = $shards[ floor( $vbucket / (self::FRAG_SIZE / $ct )) ];
        }
        return $this->map;
    }
    
    public function resolve( array $ids ){
        $map = array();
        if( ! is_array( $ids ) || count( $ids ) < 1 ) return $map;        
        // use a ruleset
        foreach( $ids as $id ){
            $shard = $this->shard( $id );
            if( strlen( $shard ) < 1 ) continue;
            if( ! isset($map[$shard]) ) $map[$shard] = array();
            $map[$shard][] = $id;
        }
        // put into the cache.
        return $map;
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
