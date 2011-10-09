<?php
namespace Gaia\Shortcircuit\Iface;

interface Resolver {
    public function search( $name, $type );
    public function get($name, $type );
    public function appdir();
    public function setAppDir( $dir );
}
