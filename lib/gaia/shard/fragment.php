<?php
namespace Gaia\Shard;

class Fragment {

    protected $f = '';
    const FRAG_SIZE = 1000;
    
    // either pass in a string, or pass in an array of frabment => shard  key value pairs.
    // array(0=>1, ... )
    // '|0-1:| ...'
    public function __construct( $fragments = NULL ){
        if( is_array( $fragments ) && count( $fragments ) == self::FRAG_SIZE ){
            $f = '|';
            foreach( $fragments as $fragment => $shard ) {
                if( is_scalar( $shard ) && ctype_digit( strval( $shard ) ) ){
                    $f .= $fragment . '-' . $shard . '|';
                }
            }
            $this->f = $f;
        } elseif( preg_match('/^\|[0-9\-\|]+\|$/', strval($fragments)) ) {
            $this->f = $fragments;
        }
    }
    
    public function populate( array $shards ){
        $list = '|';
        $ct = count( $shards );
        for( $i = 0; $i < self::FRAG_SIZE; $i++){
            $shard = $shards[ floor( $i / (self::FRAG_SIZE / $ct )) ];
            $list .= $i . '-' . $shard .'|';
        }
        return $this->f = $list;
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
        $key = '|' . (self::hash($id) % self::FRAG_SIZE ) . '-';
        $pos = strpos( $this->f, $key );
        if( $pos === FALSE ) return NULL;
        $pos += strlen( $key );
        return substr($this->f, $pos, strpos($this->f, '|', $pos + 1 ) - $pos);
    }
    
    public function shards(){        
        $shards = array();
        $pos = strpos($this->f, '-') + 1;
        while( TRUE ){
            $next_pos = strpos($this->f, '-', $pos + 1);
            if( $next_pos === FALSE ) break;
            $key = substr( $this->f, $pos, strpos( $this->f, '|', $pos + 1) - $pos );
            $pos = $next_pos + 1;
            $shards[ $key ] = $key;
        }
        sort( $shards );
        return array_values( $shards );
    }
    
    public function exportString(){
        return $this->f;
    }
    
    public function export(){
        $map = array();
        foreach( explode('|', $this->f) as $data ){
            if( ! $data ) continue;
            list( $fragment, $shard) = explode('-', $data );
            $map[ $fragment ] = $shard;
        }
        return $map;
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
