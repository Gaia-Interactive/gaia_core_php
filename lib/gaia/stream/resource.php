<?php
namespace Gaia\Stream;
use Gaia\Exception;

class Resource extends \StdClass
{
    public $stream;
    
    public $read;
    public $write;
    
    public $in = '';
    public $out = '';
    
    
    public function __construct( $stream ){
        if( ! is_resource( $stream ) ) throw new Exception('invalid stream resource');
        $this->stream = $stream;       
    }

    public function stream(){
        return $this->stream;
    }
    
    public function read(){
        if( ! $this->read instanceof \Closure ) return;
        $cb = $this->read;
        return $cb( $this );
    }
    
    public function write(){
        if( ! $this->write instanceof \Closure ) return;
        $cb = $this->write;
        return $cb( $this );
    }
    
}