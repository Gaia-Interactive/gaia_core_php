<?php
namespace Gaia\EnumPath;

interface Iface {

    public function spawn( $parent = NULL );
    public function alter( $id, $parent );
    public function idsInPath( $path );
    public function pathById( $input );
    public function separator();    
    
}