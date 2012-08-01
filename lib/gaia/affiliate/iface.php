<?php
namespace Gaia\Affiliate;

interface Iface {   
    public function search( array $identifiers );
    public function get( array $astral_ids );
    public function findRelated( array $identifiers );
    public function joinRelated( array $related );
    public function join( array $identifiers );
    public function delete( array $identifiers );
}
