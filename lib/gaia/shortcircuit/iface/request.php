<?php
namespace Gaia\Shortcircuit\Iface;

interface Request {
    public function getArgs();
    public function setArgs( array $v );
    public function action();
    public function uri();
    public function get( $key );
}
