<?php
namespace Gaia\Shortcircuit\Iface;

interface Controller {
    public function execute($name, $strict = TRUE );
    public function resolveRoute( $name );
    public function request();
}
