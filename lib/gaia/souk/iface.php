<?php

namespace Gaia\Souk;

/**
 * @package GAIAONLINE
 * @copyright 2003-present GAIA Interactive, Inc.
 * @license private/license/GAIAONLINE.txt
 */
interface Iface {
    public function app();
    public function user();
    public function auction( $l, array $data = NULL );
    public function close( $id, array $data = NULL );
    public function buy($id, array $data = NULL );
    public function bid( $id, $bid, array $data = NULL );
    public function get( $id, $lock = FALSE );
    public function fetch( array $ids, $lock = FALSE);
    public function search( $options );
    public function pending( $age = 0 );
}

// EOC
