<?php
namespace Gaia\Shortcircuit\Iface;

interface Request {
    public function args();
    public function setArgs( array $v );
    public function action();
    public function uri();
    public function base();
    public function get( $key );
}
