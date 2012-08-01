<?php
namespace Gaia\Affiliate;

interface Iface {  
    // accessors
    public function affiliations( array $identifiers );
    public function identifiers( array $affiliations );
    public function related( array $identifiers );
    
    // mutators
    public function join( array $identifiers );
    public function delete( array $identifiers );
    
    // for internal use only.
    public function _joinRelated( array $related );

}
