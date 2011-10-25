<?php
namespace Gaia\Job;
use Gaia\Container;
use Gaia\Pheanstalk;

class Config extends Container
{
    protected $conns = array();
    
    protected $handler;
    
    protected $builder;
    
    protected $refresh_interval = 60;
    
    protected $queue_prefix = '';
    
    protected $retries = 0;
    
    protected $queue_rates = array();
    
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
    
    public function handler(){
        return $this->handler;
    }
    
    public function builder(){
        return $this->builder;
    }
    
    public function setHandler( $handler ){
        if( is_callable( $handler ) ) return $this->handler = $handler;
    }
    
    public function setBuilder( $builder ){
        if( is_callable( $builder ) ) return $this->builder = $builder;
    }
    
    public function setRefreshInterval( $v ){
        return $this->refresh_interval = intval( $v );
    }
    
    public function refreshInterval(){
        return $this->refresh_interval;
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