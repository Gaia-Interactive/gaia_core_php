<?php
require __DIR__ . '/../lib/setup.php';
Gaia\DB\Connection::load( array('test'=>function () {
    $db = new Gaia\DB\Driver\PDO('mysql://host=127.0.0.1;dbname=test');
    $db->setAttribute(\PDO::ATTR_STATEMENT_CLASS, array('Gaia\DB\Driver\PDOStatement', array($db)));
    $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT );
    return $db;
}
) );