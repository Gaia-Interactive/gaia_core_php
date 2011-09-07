<?php
namespace Gaia\DB;

interface IFace {
    public function begin();
    public function rollback();
    public function commit();
}