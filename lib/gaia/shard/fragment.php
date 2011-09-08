<?php
namespace Gaia\Shard;

class Fragment {

    protected $f = array();
    const FRAG_SIZE = 1000;
    
    // either pass in a string, or pass in an array of frabment => shard  key value pairs.
    // array(0=>1, ... )
    // '|0-1:| ...'
    public function __construct( $fragments = NULL ){
        if( is_array( $fragments ) ){
            foreach( $fragments as $fragment => $shard ) {
                $this->f[ $fragment ] = $shard;
            }
        }
    }
    
    public function populate( array $shards ){
        $ct = count( $shards );
        for( $i = 0; $i < self::FRAG_SIZE; $i++){
            $this->f[ $i ] = $shards[ floor( $i / (self::FRAG_SIZE / $ct )) ];
        }
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
        $key = (self::hash($id) % self::FRAG_SIZE );
        if( isset( $this->f[ $key ] ) ) return $this->f[ $key ];
        return 0;
    }
    
    public function shards(){        
        return array_unique( $this->f );
    }
    
    public function export(){
        $this->f;
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
