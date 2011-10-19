<?php
namespace Gaia\Shortcircuit\Iface;

interface Controller  {
    public function execute($name, $strict = TRUE );
    public function request();
}
