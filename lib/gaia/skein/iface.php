<?php
namespace Gaia\Skein;

interface Iface {
    public function count();
    public function get( $id );
    public function add( $data );
    public function store( $id, $data );
    public function ascending( $limit = 1000, $start_after = NULL );
    public function descending( $limit = 1000, $start_after = NULL );
    public function filterAscending( \Closure $c, $start_after = NULL );
    public function filterDescending( \Closure $c, $start_after = NULL );
    public function shardSequences(); /* used internally only, or for admin purposes */
}
