<?php
namespace Gaia;

class Pheanstalk extends \Pheanstalk {
    protected $hostinfo;
    
	public function __construct($host, $port = self::DEFAULT_PORT, $connectTimeout = null){
	    if( strpos($host,':') !== false){
	        list( $host, $port ) = explode(':', $host, 2);
	    }
	    parent::__construct($host, $port = self::DEFAULT_PORT, $connectTimeout = null);
        $this->hostinfo = $host . ':' . $port;
	}
    public function hostInfo(){
        return $this->hostinfo;
    }
}
