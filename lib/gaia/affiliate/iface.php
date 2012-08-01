<?php
namespace Gaia\Affiliate;

interface Iface {   
    public function affiliations( array $identifiers ); // search
    public function identifiers( array $affiliations ); // get
    public function findRelated( array $identifiers );
    public function joinRelated( array $related );
    public function join( array $identifiers );
    public function delete( array $identifiers );
}
