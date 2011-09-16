<?php
namespace Gaia;
require __DIR__ . '/../../vendor/pheanstalk/pheanstalk_init.php';

class Pheanstalk extends \Pheanstalk {
    protected $hostinfo;
    
	public function __construct($host, $port = self::DEFAULT_PORT, $connectTimeout = null){
	    parent::__construct($host, $port = self::DEFAULT_PORT, $connectTimeout = null);
        $this->hostinfo = $host . ':' . $port;
	}
    public function hostInfo(){
        return $this->hostinfo;
    }
}
