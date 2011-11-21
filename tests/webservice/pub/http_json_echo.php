<?php
if( empty( $_POST ) && $raw = file_get_contents("php://input") ){
    $_REQUEST['__raw__'] = $raw;
}
if( isset( $_SERVER['HTTP_METHOD'] ) ) header('X-Request-Method: ' . $_SERVER['HTTP_METHOD']);
if( isset($_SERVER['REQUEST_METHOD']) ) header('X-Request-Method: ' . $_SERVER['REQUEST_METHOD']);
echo( json_encode( $_REQUEST ) );

?>