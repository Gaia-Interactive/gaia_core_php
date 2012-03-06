<?php
include __DIR__ . '/../common.php';

function randomDeviceToken(){
    static $chars;
    if( ! isset( $chars ) ) $chars = '0123456789abcdef';
    $token = '';
    $len = strlen( $chars ) - 1;
    for( $i = 0; $i < 64; $i++){
        $token .= $chars[ mt_rand(0, $len ) ];
    }
    return $token;
}
