<?php
if( empty( $_POST ) && $raw = file_get_contents("php://input") ){
    $_REQUEST['__raw__'] = $raw;
}

echo( json_encode( $_REQUEST ) );

?>