<?php
namespace Gaia\DB;

interface IFace {
    public function start();
    public function rollback();
    public function commit();
    public function execute($query);
    public function format_query($query);
    public function format_query_args( $query, array $args );
    public function isa( $name );
}