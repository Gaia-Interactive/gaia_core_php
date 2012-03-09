<?php
namespace Gaia\APN;
use Gaia\Exception;
use Gaia\Container;

class WrapNotice extends Container {
    
    protected $core;
    
    public function __construct( $binary = NULL ){
        if( $binary instanceof Notice ){
            $this->core = $binary;
            return;
        }
        $this->core = new Notice();
        if( $binary === NULL ) return;
        $this->unserialize( $binary );
    }
    
    public function core(){
        return $this->core;
    }
    
    public function serialize(){
        $data = $this->all();
        if( empty( $data ) ){
            $data = '';
        } else {
            $data = json_encode( $data );
        }
        $len = strlen($data);
        return pack('n', $len ) . $data . $this->core->serialize();
    }
    
    public function unserialize( $binary ){
        if( $len = strlen($binary) < 2 ) {
            throw new Exception('invalid binary notification');
        }
        $parts = unpack('nlen', $binary);
        if( ! $parts || ! isset( $parts['len'] ) ) {
            throw new Exception('invalid binary notification');
        }
        $meta = substr($binary, 2, $parts['len']);
        if( $meta ){
            $this->load( ( $v = json_decode($meta, TRUE ) ) );
        }
        $this->core->unserialize(substr( $binary, $parts['len']  + 2) );
    }
    
    public function __call( $method, array $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }
}
