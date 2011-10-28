<?php
namespace Gaia\DB;

interface IFace {
    public function begin($auth = NULL);
    public function rollback($auth = NULL);
    public function commit($auth = NULL);
    public function execute($query);
    public function format_query($query);
    public function format_query_args( $query, array $args );
}