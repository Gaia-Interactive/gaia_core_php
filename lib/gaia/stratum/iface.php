<?php
namespace Gaia\Stratum;

interface Iface {
    public function query( array $params );
    public function store( $constraint, $stratum );
}
