<?php
include __DIR__ . '/../../common.php';
if( ! isset( $_SERVER['HTTP_X_SERIALIZE'] ) ) die('no serialization method specified');
$class = '\Gaia\Serialize\\' . $_SERVER['HTTP_X_SERIALIZE'];
if( ! class_exists( $class ) ) die('invalid serialization: ' . $class);
$s = new $class;
print $s->serialize( $s->unserialize(  file_get_contents("php://input") ) );
