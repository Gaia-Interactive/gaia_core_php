<?php
namespace Gaia\APN\Connection;
use Gaia\Exception;

class Production extends \Gaia\APN\Connection {
    public static $url = 'ssl://gateway.push.apple.com:2195';

    public function __construct( $ssl_cert ){
        $ssl_opts = array('verify_peer' => FALSE, 'local_cert' => $ssl_cert);
        if ( ! is_readable($ssl_cert)) {
            throw new Exception("Unable to read certificate file '{$ssl_cert}'");
        }
        $url = static::$url;
        $context = stream_context_create(array('ssl' => $ssl_opts));
        $stream = stream_socket_client($url, $errno, $error,
            $connect_timeout = 1, STREAM_CLIENT_CONNECT, $context);
        if (!$stream) {
            throw new Exception("Unable to connect to '{$url}': {$error} ({$errno})");
        }    
        parent::__construct( $stream );
    }
}