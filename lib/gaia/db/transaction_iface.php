<?php
namespace Gaia\DB;

interface Transaction_Iface {
    public function begin();
    public function rollback();
    public function commit();
}