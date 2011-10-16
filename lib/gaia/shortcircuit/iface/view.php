<?php
namespace Gaia\Shortcircuit\Iface;

interface View {
    public function render( $name );
    public function fetch( $name );
    public function request();
}
