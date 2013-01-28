<?php
namespace Gaia\Serialize;

class Compress implements Iface {

    protected $s;
    protected $threshold = 1000;
    
    const PREFIX = '#__GZIP__:';
    const LEN = 10;
    
    public function __construct( Iface $s = NULL, $threshold = 1000 ){
        if( ! $s  ) $s = new PHP('');
        $this->s = $s;
        $threshold = intval( $threshold );
        if( $threshold > 0 ) $this->threshold = $threshold;
    }

    public function serialize( $data ){
        return $this->compress($this->s->serialize( $data ));
    }
    
    public function unserialize($payload) {
        if(! is_scalar( $payload ) ) return NULL;
        $payload = $this->uncompress($payload);
        if( ! is_scalar( $payload ) ) return NULL;
        return $this->s->unserialize($payload);
    }
    
    protected function compress( $input ){
        return strlen( $input ) > $this->threshold ? self::PREFIX . gzcompress( $input ) : $input;
    }
    
    protected function uncompress($input) {
        if( substr( $input, 0, self::LEN ) != self::PREFIX ) return $input;
        $v = gzuncompress( substr( $input, self::LEN ));
        if( $v === FALSE ) throw new \Exception('gzip decompression error');
        return $v;
    }
}