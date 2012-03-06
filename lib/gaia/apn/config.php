<?php
namespace Gaia\APN;
use Gaia\Container;
use Gaia\Pheanstalk;

class Config extends Container
{
    protected static $instance;
    
    protected $conns = array();
    
    protected $queue_prefix = 'APN';
    
    protected $queue_rates = array();
    
    
    public static function instance(){
        if( ! self::$instance ) self::$instance = new self;
        return self::$instance;
    }
    
        
    public function setConnections( array $conns ){
        $this->connections = array();
        $this->addConnections( $conns );
    }
    
    public function addConnections( array $conns ){
        foreach( $conns as $conn ) $this->addConnection( $conn );
    }
    
    public function addConnection( $conn ){
        if( ! $conn instanceof Pheanstalk ) $conn = new Pheanstalk( $conn );
        return $this->conns[$conn->hostInfo()] = $conn;
    }
    
    public function connection( $k ){
        return isset( $this->conns[ $k ] ) ? $this->conns[ $k ] : NULL;
    }
    
    public function connections(){
        return $this->conns;
    }
    
    public function setQueuePrefix( $v ){
        return $this->queue_prefix = $v;
    }
    
    public function queuePrefix(){
        return $this->queue_prefix;
    }
    
    public function setRetries( $v ){
        return $this->retries = intval( $v );
    }
    
    public function retries(){
        return $this->retries;
    }
    
    public function queueRates(){
        return $this->queue_rates;
    }
    
    public function addQueueRate( $pattern, $rate = 0 ){
        $this->queue_rates[ $pattern ] = $rate;
    }
    
    public function setQueueRates( array $patterns ){
        $this->queue_rates = $patterns;
    }
}