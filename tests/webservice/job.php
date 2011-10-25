<?php
include __DIR__ . '/../common.php';

if( isset( $_GET['register'] ) ){
    print json_encode( array('connections'=>array('127.0.0.1:11300'), 'queue_rates'=>array(), 'retries'=>3) );
    exit;
}

if( ! isset( $_GET['signed'] ) ){
    die( "\n<h1>ok</h1>\n");
}

use Gaia\Job;

$nonce = new Gaia\Nonce('test001');
$server = new Gaia\Container( $_SERVER );
$hash = $server->HTTP_X_JOB_NONCE;
$id = $server->HTTP_X_JOB_ID;
$uri = $server->REQUEST_URI;
$valid = $nonce->check($hash, $uri );
$status = ( $valid ) ? 'complete' : 'failed';
if( $id ) header('X-JOB-ID: ' . $id);

if( $id && $valid ){
    Job::config()->addConnection( new Pheanstalk('127.0.0.1', '11300' ) );
    $job = Job::find( $id );
    if( ! $job->complete() ) {
        $status = 'failed-to-mark-complete';
        $valid = FALSE;
    }

}
header('X-JOB-STATUS: ' . $status);
if( ! $valid ) header( $server->SERVER_PROTOCOL . ' 403 Forbidden');
echo "\n<h1>$status</h1>\n";

// EOF
