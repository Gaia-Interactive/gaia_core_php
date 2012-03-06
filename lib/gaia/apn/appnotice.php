<?php
namespace Gaia\APN;
use Gaia\Exception;

class AppNotice {
    
    protected $core;
    
    const SEP = '|';
    
    public function __construct( $binary = NULL ){
        if( $binary instanceof Notice ){
            $this->core = $binary;
            return;
        }
        $this->core = new Notice();
        if( $binary === NULL ) return;
        $this->unserialize( $binary );
    }
    
    public function setApp( $v ){
        $this->app = $v;
    }
    
    public function getApp(){
        return $this->app;
    }
    
    
    public function core(){
        return $this->core;
    }
    
    public function serialize(){
        return $this->app . self::SEP . $this->core->serialize();
    }
    
    public function unserialize( $binary ){
        if( strpos($binary, self::SEP) === FALSE ) {
            throw new Exception('invalid binary notification');
        }
        list( $app, $binary_notification ) = explode(self::SEP, $binary, 2);
        $this->app = $app;
        $this->core->unserialize($binary_notification);
    }
    
    public function __call( $method, array $args ){
        return call_user_func_array( array( $this->core, $method ), $args );
    }
    
    public function __isset( $k ){
        return isset( $this->core->$k );
    }
    
    public function __get( $k ){
        return $this->core->$k;
    }
    
    public function __set( $k, $v ){
        return $this->core->$k = $v;
    }

}
