<?php
namespace Gaia\Identifier;

class Wrap implements Iface {
    
    protected $core;
    
    public function __construct( Iface $core ){
        $this->core = $core;
    }
    
    public function byId( $request ){
        return $this->core->byId( $request );
    }
    
    public function byName( $request  ){
        return $this->core->byName( $request );
    }
    
    public function delete( $id, $name ){
        return $this->core->delete( $id, $name );
    }
    
    public function store( $id, $name, $strict = FALSE ){
        return $this->core->store( $id, $name, $strict );
    }
    
    public function batch( \Closure $closure, array $options = NULL ){
        return $this->core->batch( $closure, $options );
    }

    public function __call( $method, array $args ){
        return call_user_func_array(array($this->core, $method), $args );
    }
}