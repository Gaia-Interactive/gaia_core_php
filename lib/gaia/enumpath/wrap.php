<?php
namespace Gaia\EnumPath;

class Wrap implements Iface {
    
    protected $core;
    
    public function __construct( Iface $core ){
        $this->core = $core;
    }
    
    public function spawn( $parent = NULL ){
        return $this->core->spawn( $parent );        
    }
    
    public function alter( $id, $parent ){
        return $this->core->alter( $id, $parent );
    }
    
    public function idsInPath( $path ){
        return $this->core->idsInPath( $path );
    }

    public function pathById( $input ){
        return $this->core->pathById( $input );
    }
    
    public function separator(){
        return $this->core->separator();
    }
    
    public function __call( $method, array $args ){
        return call_user_func_array(array($this->core, $method), $args );
    }
}