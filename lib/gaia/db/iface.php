<?php
namespace Gaia\DB;

interface IFace {
    public function start();
    public function rollback();
    public function commit();
    public function execute($query);
    public function prep($query);
    public function prep_args($query, array $args);
    public function isa( $name );
    public function hash();
}