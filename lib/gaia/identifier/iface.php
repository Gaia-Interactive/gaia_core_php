<?php
namespace Gaia\Identifier;

interface Iface {   
    public function byId( $request );
    public function byName( $request  );
    public function delete( $id, $name );
    public function store( $id, $name, $strict = FALSE );
    public function batch( \Closure $closure, array $options = NULL );
}