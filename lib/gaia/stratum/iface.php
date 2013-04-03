<?php
namespace Gaia\Stratum;

interface Iface {
    public function batch( array $params );
    public function query( array $params );
    public function store( $constraint, $stratum );
    public function delete( $constraint );
}
