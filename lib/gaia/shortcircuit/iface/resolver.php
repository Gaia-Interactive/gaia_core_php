<?php
namespace Gaia\Shortcircuit\Iface;

interface Resolver {
    public function match( $name, & $args );
    public function link( $name, array $args = array());
    public function get($name, $type );
    public function appdir();
    public function setAppDir( $dir );
}
